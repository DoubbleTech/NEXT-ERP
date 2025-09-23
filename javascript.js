// chat.js - Client-Side Logic for FinLab ERP Chat Feature

// Initialize appConfig with defaults if not defined
if (typeof appConfig === 'undefined') {
    window.appConfig = {
        websocketUrl: 'ws://localhost:8080',
        userId: null,
        wsToken: null,
        userName: 'Guest'
    };
    console.warn('appConfig was not defined. Using default values.');
}

// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', () => {
    // --- Element References ---
    const chatContainer = document.getElementById('chat-container');
    const collapseButton = document.getElementById('collapse-chat-btn');
    const collapsedIcon = document.getElementById('chat-icon-collapsed');
    const contactSearchInput = document.getElementById('contact-search');
    const contactListUL = document.getElementById('contact-list-ul');
    const contactItems = contactListUL ? contactListUL.querySelectorAll('.contact-item') : [];
    const optionsToggleButton = document.getElementById('chat-options-toggle-btn');
    const optionsMenu = document.getElementById('chat-options-menu');
    const chatSearchButton = document.getElementById('chat-search-btn');
    const viewAttachmentsButton = document.getElementById('view-attachments-btn');
    const messageSearchContainer = document.getElementById('message-search-container');
    const messageSearchInput = document.getElementById('message-search-input');
    const messageArea = document.querySelector('.message-area');
    const messageList = document.getElementById('message-list');
    const messageInput = document.getElementById('message-input');
    const sendButton = document.getElementById('send-button');
    const partnerNameEl = document.querySelector('.message-area-header .chat-partner-name');
    const footerAttachButton = document.getElementById('attach-button');
    const footerCallButton = document.getElementById('call-button');

    // Validate essential elements
    if (!messageList || !messageInput || !sendButton || !chatContainer || !collapsedIcon || !partnerNameEl) {
        console.error("Chat Error: Essential chat UI elements not found! Check IDs in chat.php.");
        return;
    }

    // --- State Management ---
    let socket = null;
    let currentTargetUserId = null;
    let connectionAttempts = 0;
    const MAX_RECONNECT_ATTEMPTS = 5;
    const RECONNECT_DELAY = 5000; // 5 seconds

    // --- WebSocket Connection ---
    function connectWebSocket() {
        // Check browser support
        if (typeof WebSocket === 'undefined') {
            displaySystemMessage('WebSockets are not supported in your browser.', true);
            return;
        }

        // Prevent multiple connections
        if (socket && (socket.readyState === WebSocket.CONNECTING || socket.readyState === WebSocket.OPEN)) {
            console.log('WebSocket already connecting or open.');
            return;
        }

        console.log(`Attempting to connect to WebSocket: ${appConfig.websocketUrl}`);
        displaySystemMessage('Connecting to chat...');

        try {
            socket = new WebSocket(appConfig.websocketUrl);

            // WebSocket Event Listeners
            socket.onopen = handleSocketOpen;
            socket.onmessage = handleSocketMessage;
            socket.onerror = handleSocketError;
            socket.onclose = handleSocketClose;
        } catch (error) {
            console.error('WebSocket initialization error:', error);
            displaySystemMessage('Connection error. Please try again later.', true);
        }
    }

    function handleSocketOpen(event) {
        console.log('WebSocket connection opened:', event);
        connectionAttempts = 0; // Reset on successful connection
        displaySystemMessage('Connected.');

        // Authenticate
        if (appConfig.userId && appConfig.wsToken) {
            socket.send(JSON.stringify({
                type: 'auth',
                userId: appConfig.userId,
                token: appConfig.wsToken
            }));
        } else {
            console.warn('Cannot authenticate WebSocket: Missing user ID or token.');
            displaySystemMessage('Authentication failed. Chat may not work correctly.', true);
        }
    }

    function handleSocketMessage(event) {
        console.log('Message received:', event.data);
        try {
            const messageData = JSON.parse(event.data);

            switch (messageData.type) {
                case 'message':
                    handleIncomingMessage(messageData);
                    break;
                case 'system':
                    displaySystemMessage(messageData.text);
                    break;
                case 'error':
                    displaySystemMessage(`Error: ${messageData.text}`, true);
                    break;
                case 'history':
                    loadMessageHistory(messageData.messages);
                    break;
                case 'presence':
                    updateUserPresence(messageData);
                    break;
                default:
                    console.log('Unhandled message type:', messageData.type);
            }
        } catch (error) {
            console.error('Error parsing message:', error, 'Raw data:', event.data);
            displaySystemMessage('Error processing message.', true);
        }
    }

    function handleSocketError(event) {
        console.error('WebSocket error:', event);
        displaySystemMessage('Connection error occurred.', true);
    }

    function handleSocketClose(event) {
        console.log('WebSocket connection closed:', event);
        displaySystemMessage('Disconnected. Attempting to reconnect...');
        socket = null;

        if (connectionAttempts < MAX_RECONNECT_ATTEMPTS) {
            const delay = Math.min(RECONNECT_DELAY * Math.pow(2, connectionAttempts), 30000);
            connectionAttempts++;
            setTimeout(connectWebSocket, delay);
        } else {
            displaySystemMessage('Failed to reconnect after multiple attempts. Please refresh.', true);
        }
    }

    // --- Message Handling ---
    function handleIncomingMessage(messageData) {
        const isCurrentChat = messageData.senderId === currentTargetUserId || 
                            (messageData.targetUserId && messageData.targetUserId === appConfig.userId);
        
        const messageTypeClass = (messageData.senderId === appConfig.userId) ? 
            'message-sent' : 'message-received';
        
        const displayText = messageData.senderName ? 
            `${messageData.senderName}: ${messageData.text}` : messageData.text;
        
        if (isCurrentChat) {
            displayMessage(displayText, messageTypeClass);
        } else {
            // Notification for messages in other chats
            console.log('Message received in different chat:', messageData);
        }
    }

    function loadMessageHistory(messages) {
        messageList.innerHTML = '';
        if (messages && messages.length > 0) {
            messages.forEach(msg => {
                const messageTypeClass = (msg.senderId === appConfig.userId) ? 
                    'message-sent' : 'message-received';
                displayMessage(msg.text, messageTypeClass);
            });
        } else {
            displaySystemMessage('No previous messages found.');
        }
    }

    function updateUserPresence(presenceData) {
        // Update contact list with online/offline status
        contactItems.forEach(item => {
            const userId = item.dataset.userId;
            if (userId && presenceData[userId]) {
                const statusIndicator = item.querySelector('.contact-status');
                if (statusIndicator) {
                    statusIndicator.className = `contact-status ${presenceData[userId]}`;
                }
            }
        });
    }

    // --- UI Functions ---
    function displayMessage(text, typeClass = 'message-received') {
        const messageElement = document.createElement('div');
        messageElement.classList.add('message', typeClass);
        messageElement.textContent = sanitizeText(text);
        messageList.appendChild(messageElement);
        scrollToBottom();
    }

    function displaySystemMessage(text, isError = false) {
        const systemMessageElement = document.createElement('div');
        systemMessageElement.classList.add('message-system');
        if (isError) {
            systemMessageElement.style.color = 'var(--error-color)';
            systemMessageElement.style.fontWeight = 'bold';
        }
        systemMessageElement.textContent = sanitizeText(text);
        messageList.appendChild(systemMessageElement);
        scrollToBottom();
    }

    function sanitizeText(text) {
        return text.toString()
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function scrollToBottom() {
        messageList.scrollTop = messageList.scrollHeight;
    }

    // --- Message Sending ---
    function handleSendMessage() {
        const messageText = messageInput.value.trim();
        if (!messageText) return;

        // Optimistic UI update
        displayMessage(messageText, 'message-sent');

        // Prepare payload
        const messagePayload = {
            type: 'message',
            text: messageText,
            senderId: appConfig.userId,
            senderName: appConfig.userName || 'Me',
            targetUserId: currentTargetUserId,
            timestamp: new Date().toISOString()
        };

        // Send via WebSocket
        if (socket && socket.readyState === WebSocket.OPEN) {
            try {
                socket.send(JSON.stringify(messagePayload));
                console.log('Message sent:', messagePayload);
            } catch (error) {
                console.error("Failed to send message:", error);
                displaySystemMessage("Failed to send message. Trying again...", true);
                // Retry after delay
                setTimeout(() => socket.send(JSON.stringify(messagePayload)), 1000);
            }
        } else {
            console.warn('Cannot send: WebSocket not open');
            displaySystemMessage('Not connected. Message will be sent when connection is restored.', true);
            // TODO: Queue messages for when connection is restored
        }

        messageInput.value = '';
        messageInput.focus();
    }

    // --- UI Event Listeners ---
    // Send message
    sendButton.addEventListener('click', handleSendMessage);
    messageInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSendMessage();
        }
    });

    // Collapse/expand chat
    if (collapseButton && chatContainer && collapsedIcon) {
        collapseButton.addEventListener('click', () => {
            chatContainer.classList.add('collapsed');
            collapsedIcon.style.display = 'flex';
        });
    }

    // Draggable collapsed icon
    if (collapsedIcon && chatContainer) {
        let isDragging = false;
        let hasDragged = false;
        let startX, startY, iconStartX, iconStartY;

        collapsedIcon.addEventListener('mousedown', (e) => {
            if (e.button !== 0) return;
            isDragging = true;
            hasDragged = false;
            startX = e.clientX;
            startY = e.clientY;
            iconStartX = collapsedIcon.offsetLeft;
            iconStartY = collapsedIcon.offsetTop;
            collapsedIcon.style.cursor = 'grabbing';
            e.preventDefault();
        });

        collapsedIcon.addEventListener('mouseup', (e) => {
            if (e.button !== 0) return;
            if (isDragging && !hasDragged) {
                chatContainer.style.display = 'flex';
                requestAnimationFrame(() => {
                    chatContainer.classList.remove('collapsed');
                    collapsedIcon.style.display = 'none';
                });
            }
            isDragging = false;
            hasDragged = false;
            collapsedIcon.style.cursor = 'grab';
        });

        document.addEventListener('mousemove', (e) => {
            if (!isDragging || !collapsedIcon) return;
            
            const deltaX = e.clientX - startX;
            const deltaY = e.clientY - startY;
            
            if (Math.abs(deltaX) > 5 || Math.abs(deltaY) > 5) {
                hasDragged = true;
            }

            let newX = iconStartX + deltaX;
            let newY = iconStartY + deltaY;
            const viewportWidth = window.innerWidth;
            const viewportHeight = window.innerHeight;
            const iconWidth = collapsedIcon.offsetWidth;
            const iconHeight = collapsedIcon.offsetHeight;

            newX = Math.max(0, Math.min(newX, viewportWidth - iconWidth));
            newY = Math.max(0, Math.min(newY, viewportHeight - iconHeight));

            collapsedIcon.style.left = `${newX}px`;
            collapsedIcon.style.top = `${newY}px`;
            collapsedIcon.style.bottom = 'auto';
            collapsedIcon.style.right = 'auto';
        });

        document.addEventListener('mouseup', (e) => {
            if (e.button !== 0) return;
            if (isDragging) {
                isDragging = false;
                hasDragged = false;
                collapsedIcon.style.cursor = 'grab';
            }
        });

        document.addEventListener('mouseleave', () => {
            if (isDragging) {
                isDragging = false;
                hasDragged = false;
                collapsedIcon.style.cursor = 'grab';
            }
        });
    }

    // Contact search
    if (contactSearchInput && contactItems.length > 0) {
        contactSearchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase().trim();
            contactItems.forEach(item => {
                const nameElement = item.querySelector('.contact-name');
                const department = item.dataset.department?.toLowerCase() || '';
                const name = nameElement ? nameElement.textContent.toLowerCase() : '';
                const isMatch = searchTerm === '' || name.includes(searchTerm) || department.includes(searchTerm);
                item.classList.toggle('hidden', !isMatch);
            });
        });
    }

    // Contact selection
    if (contactItems.length > 0) {
        contactItems.forEach(item => {
            item.addEventListener('click', () => {
                // Update UI
                contactItems.forEach(i => i.classList.remove('active'));
                item.classList.add('active');
                
                // Set current chat partner
                currentTargetUserId = item.dataset.userId;
                const contactName = item.querySelector('.contact-name')?.textContent || 'User';
                partnerNameEl.textContent = contactName;
                
                // Clear and load messages
                messageList.innerHTML = '';
                displaySystemMessage(`Chat with ${contactName}`);
                
                // Request message history
                if (socket && socket.readyState === WebSocket.OPEN) {
                    socket.send(JSON.stringify({
                        type: 'get_history',
                        targetUserId: currentTargetUserId
                    }));
                }
            });
        });
    }

    // Options menu toggle
    if (optionsToggleButton && optionsMenu) {
        optionsToggleButton.addEventListener('click', (e) => {
            e.stopPropagation();
            const isActive = optionsMenu.classList.toggle('active');
            optionsToggleButton.classList.toggle('active');
            const icon = optionsToggleButton.querySelector('i');
            
            if (isActive) {
                icon.classList.replace('fa-plus', 'fa-times');
                optionsToggleButton.title = "Close Options";
                messageSearchContainer?.classList.remove('active');
            } else {
                icon.classList.replace('fa-times', 'fa-plus');
                optionsToggleButton.title = "More Options";
            }
        });
    }

    // Message search toggle
    if (chatSearchButton && messageSearchContainer) {
        chatSearchButton.addEventListener('click', (e) => {
            e.stopPropagation();
            messageSearchContainer.classList.toggle('active');
            
            if (messageSearchContainer.classList.contains('active')) {
                messageSearchInput.focus();
                optionsMenu?.classList.remove('active');
                if (optionsToggleButton) {
                    optionsToggleButton.classList.remove('active');
                    const icon = optionsToggleButton.querySelector('i');
                    icon.classList.replace('fa-times', 'fa-plus');
                    optionsToggleButton.title = "More Options";
                }
            }
        });
    }

    // Attachments button
    if (viewAttachmentsButton) {
        viewAttachmentsButton.addEventListener('click', (e) => {
            e.stopPropagation();
            displaySystemMessage('Attachment viewing coming soon!');
            optionsMenu?.classList.remove('active');
            if (optionsToggleButton) {
                optionsToggleButton.classList.remove('active');
                const icon = optionsToggleButton.querySelector('i');
                icon.classList.replace('fa-times', 'fa-plus');
                optionsToggleButton.title = "More Options";
            }
        });
    }

    // Footer buttons
    if (footerAttachButton) {
        footerAttachButton.addEventListener('click', () => {
            displaySystemMessage('File attachments coming soon!');
        });
    }

    if (footerCallButton) {
        footerCallButton.addEventListener('click', () => {
            displaySystemMessage('Voice/video calls coming soon!');
        });
    }

    // Close menus when clicking outside
    document.addEventListener('click', (e) => {
        if (optionsMenu?.classList.contains('active') && 
            !optionsMenu.contains(e.target) && 
            !optionsToggleButton?.contains(e.target)) {
            optionsMenu.classList.remove('active');
            if (optionsToggleButton) {
                optionsToggleButton.classList.remove('active');
                const icon = optionsToggleButton.querySelector('i');
                icon.classList.replace('fa-times', 'fa-plus');
                optionsToggleButton.title = "More Options";
            }
        }
        
        if (messageSearchContainer?.classList.contains('active') && 
            !messageSearchContainer.contains(e.target) && 
            !chatSearchButton?.contains(e.target)) {
            messageSearchContainer.classList.remove('active');
        }
    });

    // Message search functionality
    if (messageSearchInput) {
        messageSearchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const messages = messageList.querySelectorAll('.message');
            
            messages.forEach(msg => {
                const text = msg.textContent.toLowerCase();
                msg.style.display = text.includes(searchTerm) ? 'block' : 'none';
            });
        });
    }

    // Drag and drop for files
    const dropZones = [messageArea, ...contactItems].filter(zone => zone);
    dropZones.forEach(zone => {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            zone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
            });
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            zone.addEventListener(eventName, () => {
                zone.classList.add('dragover');
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            zone.addEventListener(eventName, () => {
                zone.classList.remove('dragover');
            });
        });

        zone.addEventListener('drop', (e) => {
            const files = e.dataTransfer?.files;
            if (files && files.length > 0) {
                handleDroppedFiles(files, zone);
            }
        });
    });

    function handleDroppedFiles(files, dropZone) {
        let targetInfo = "in the chat area";
        
        if (dropZone.classList.contains('contact-item')) {
            const nameEl = dropZone.querySelector('.contact-name');
            targetInfo = `on contact ${nameEl?.textContent || 'Unknown'}`;
            dropZone.click(); // Switch to this contact
        }

        Array.from(files).forEach(file => {
            if (file.size > 10 * 1024 * 1024) { // 10MB limit
                displaySystemMessage(`File "${file.name}" is too large (max 10MB).`, true);
                return;
            }

            displaySystemMessage(`Preparing to send file: ${file.name}`);
            // TODO: Implement file upload logic
        });
    }

    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        if (socket && socket.readyState === WebSocket.OPEN) {
            socket.close(1000, 'User navigated away');
        }
    });

    // Debugging helpers
    window.addEventListener('error', (event) => {
        console.error('Uncaught error:', event.error);
        displaySystemMessage('A chat error occurred. Please refresh.', true);
    });

    // Initialize connection if user is logged in
    if (appConfig.userId) {
        connectWebSocket();
    } else {
        displaySystemMessage('Please log in to use chat features.', true);
    }
});