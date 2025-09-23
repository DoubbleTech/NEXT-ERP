<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat UI Preview - Enhanced & Collapsible</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        /* Enhanced Styling for the Chat Panel */

        /* --- CSS Variables for Theming (Updated from index.php) --- */
        :root {
            --chat-font-family: 'Inter', sans-serif;
            --primary-color: #06b6d4; /* Cyan 600 */
            --primary-dark: #0891b2;  /* Cyan 700 */
            --primary-light: #a5f3fc; /* Cyan 200 */
            --primary-bg: rgba(6, 182, 212, 0.1); /* Cyan 600 with 10% alpha */
            --background-color: #ffffff;
            --container-border-color: #e2e8f0; /* Slate 200 */
            --header-footer-bg: #f8fafc; /* Slate 50 */
            --input-border-color: #e2e8f0; /* Slate 200 */
            --text-dark: #1e293b;    /* Slate 800 */
            --text-medium: #64748b;  /* Slate 500 */
            --text-light: #94a3b8; /* Slate 400 */
            --input-focus-ring: rgba(6, 182, 212, 0.25);
            --message-sent-bg: var(--primary-color);
            --message-sent-text: #ffffff;
            --message-received-bg: #F3F4F6; /* Gray 100 */
            --message-received-text: var(--text-dark);
            --status-online: #10B981; /* Emerald 500 */
            --status-offline: #9CA3AF; /* Gray 400 */
            --status-busy-call: #EF4444; /* Red 500 */
            --status-idle: #F59E0B; /* Amber 500 */
            --status-busy-task: #DC2626; /* Red 600 */
            /* Updated Drop Zone Styles */
            --drop-zone-border-style: 2px dashed var(--primary-color);
            --drop-zone-bg: var(--primary-bg);
        }

        /* --- Basic Reset & Body --- */
        * { box-sizing: border-box; }
        body { font-family: var(--chat-font-family); background-color: #eef2f7; margin: 0; padding: 20px; color: var(--text-dark); overflow: auto; }

        /* --- Chat Container (In-Flow Layout) --- */
        .chat-container { width: 100%; max-width: 800px; height: 70vh; min-height: 500px; margin: 30px auto; z-index: 1000; background-color: var(--background-color); border: 1px solid var(--container-border-color); border-radius: 16px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08); overflow: hidden; display: flex; flex-direction: column; transition: opacity 0.3s ease, transform 0.3s ease, visibility 0s linear 0.3s; visibility: visible; }
        .chat-container.collapsed { opacity: 0; transform: scale(0.9); pointer-events: none; height: 0; min-height: 0; width: 0; border: none; box-shadow: none; margin: 0; padding: 0; overflow: hidden; visibility: hidden; transition: opacity 0.3s ease, transform 0.3s ease, visibility 0s linear 0.3s; }

        /* --- Main Chat Header --- */
        .chat-header { padding: 10px 15px; background-color: var(--header-footer-bg); border-bottom: 1px solid var(--container-border-color); flex-shrink: 0; display: flex; align-items: center; justify-content: space-between; cursor: default; }
        .chat-header h3 { margin: 0; font-size: 1rem; font-weight: 600; color: var(--text-dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-right: auto; padding-right: 10px; }
        .header-buttons { display: flex; align-items: center; gap: 5px; }
        .header-icon-btn { background: none; border: none; font-size: 1rem; color: var(--text-medium); cursor: pointer; padding: 5px; line-height: 1; border-radius: 4px; transition: color 0.2s ease, background-color 0.2s ease; }
        .header-icon-btn:hover { color: var(--text-dark); background-color: var(--container-border-color); }

        /* --- Chat Body --- */
        .chat-body { flex-grow: 1; display: flex; overflow: hidden; }

        /* --- Contact List --- */
        .contact-list { width: 240px; border-right: 1px solid var(--container-border-color); overflow-y: auto; flex-shrink: 0; background-color: var(--background-color); display: flex; flex-direction: column; }
        .search-container { padding: 10px 15px; border-bottom: 1px solid var(--container-border-color); flex-shrink: 0; }
        #contact-search { width: 100%; padding: 8px 12px; border: 1px solid var(--input-border-color); border-radius: 20px; font-size: 0.9rem; font-family: var(--chat-font-family); color: var(--text-dark); }
        #contact-search::placeholder { color: var(--text-light); }
        #contact-search:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px var(--input-focus-ring); }
        .contact-list ul { list-style: none; padding: 0; margin: 0; overflow-y: auto; flex-grow: 1; }
        .contact-list .contact-item {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 1px solid var(--container-border-color);
            /* Added transition for dragover effect */
            transition: background-color 0.2s ease, transform 0.2s ease, border-color 0.2s ease;
            border: 2px solid transparent; /* Reserve space for border */
            margin: -2px 0; /* Adjust margin to compensate for border */
        }
        .contact-list .contact-item:last-child { border-bottom: none; }
        .contact-list .contact-item:hover { background-color: #f0f9ff; }
        .contact-list .contact-item.active { background-color: var(--primary-bg); }
        .contact-list .contact-item.hidden { display: none; }
        /* Dragover style for contacts */
        .contact-list .contact-item.dragover {
            border: var(--drop-zone-border-style);
            background-color: var(--drop-zone-bg);
            transform: scale(1.01); /* Subtle scale */
        }

        .contact-item .profile-pic-container { position: relative; margin-right: 12px; flex-shrink: 0; }
        .contact-item .profile-pic { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid var(--container-border-color); }
        .contact-item .status-indicator { position: absolute; bottom: 0px; right: 0px; width: 12px; height: 12px; border-radius: 50%; border: 2px solid var(--background-color); }
        .status-indicator.online { background-color: var(--status-online); }
        .status-indicator.offline { background-color: var(--status-offline); }
        .status-indicator.busy-call { background-color: var(--status-busy-call); }
        .status-indicator.idle { background-color: var(--status-idle); }
        .status-indicator.busy-task { background-color: var(--status-busy-task); }
        .contact-item .contact-info { overflow: hidden; }
        .contact-item .contact-name { font-size: 0.95rem; font-weight: 500; color: var(--text-dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 2px; }
        .contact-item .contact-preview { font-size: 0.8rem; color: var(--text-medium); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        /* --- Message Area Wrapper --- */
        .message-area {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            position: relative;
            border: 2px solid transparent; /* Reserve space for border */
            /* Added transition for dragover effect */
            transition: border-color 0.2s ease, background-color 0.2s ease, transform 0.2s ease;
        }
        /* Dragover style for message area */
        .message-area.dragover {
             border: var(--drop-zone-border-style);
             background-color: var(--drop-zone-bg);
             transform: scale(1.01); /* Subtle scale */
        }


        /* --- Message Area Header --- */
        .message-area-header { padding: 10px 15px; background-color: var(--header-footer-bg); border-bottom: 1px solid var(--container-border-color); flex-shrink: 0; display: flex; align-items: center; justify-content: space-between; }
        .message-area-header .chat-partner-name { font-weight: 600; color: var(--text-dark); font-size: 0.95rem; }
        .message-area-actions { display: flex; align-items: center; gap: 5px; position: relative; }
        .message-action-btn { background: none; border: none; font-size: 1rem; color: var(--text-medium); cursor: pointer; padding: 5px; line-height: 1; border-radius: 4px; transition: color 0.2s ease, background-color 0.2s ease; }
        .message-action-btn:hover { color: var(--text-dark); background-color: var(--container-border-color); }

        /* --- Chat Options Menu --- */
         #chat-options-menu { display: none; align-items: center; gap: 5px; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(4px); border: 1px solid var(--container-border-color); padding: 4px 8px; border-radius: 20px; position: absolute; right: 45px; top: 50%; transform: translateY(-50%); box-shadow: 0 2px 5px rgba(0,0,0,0.1); z-index: 5; }
          #chat-options-menu.active { display: flex; }
         #chat-options-menu .message-action-btn { width: 30px; height: 30px; font-size: 0.9rem; background-color: transparent; }
         #chat-options-menu .message-action-btn:hover { background-color: rgba(0,0,0,0.05); }

        /* --- Message Search Input Container --- */
        #message-search-input-container { position: absolute; top: 100%; right: 0; background-color: var(--background-color); padding: 8px; border: 1px solid var(--container-border-color); border-top: none; border-radius: 0 0 8px 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.05); z-index: 10; display: none; align-items: center; gap: 5px; }
         #message-search-input-container.active { display: flex; }
        #message-search-input { border: 1px solid var(--input-border-color); border-radius: 15px; padding: 5px 10px; font-size: 0.85rem; }
        #message-search-input:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 2px var(--input-focus-ring); }
        .search-nav-btn { background: none; border: 1px solid var(--input-border-color); border-radius: 4px; padding: 3px 5px; cursor: pointer; font-size: 0.8rem; color: var(--text-medium); }
        .search-nav-btn:hover { background-color: var(--header-footer-bg); }

        /* --- Message List --- */
        .message-list { flex-grow: 1; padding: 15px; padding-top: 15px; /* Adjusted padding */ overflow-y: auto; display: flex; flex-direction: column; background-color: #fdfdfd; }
        .message { max-width: 75%; padding: 10px 15px; margin-bottom: 12px; border-radius: 18px; font-size: 0.95rem; line-height: 1.45; word-wrap: break-word; position: relative; }
        .message-sent { background-color: var(--message-sent-bg); color: var(--message-sent-text); border-bottom-right-radius: 6px; align-self: flex-end; margin-left: auto; }
        .message-received { background-color: var(--message-received-bg); color: var(--message-received-text); border: 1px solid var(--container-border-color); border-bottom-left-radius: 6px; align-self: flex-start; margin-right: auto; }
        .message-system { font-size: 0.8rem; color: var(--text-medium); text-align: center; margin: 15px 0; align-self: center; }

        /* --- Chat Footer (Input Area) --- */
        .chat-footer { padding: 10px 15px; border-top: 1px solid var(--container-border-color); background-color: var(--header-footer-bg); display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
        .footer-icon-button { background: none; border: none; padding: 8px; font-size: 1.1rem; color: var(--text-medium); cursor: pointer; border-radius: 50%; transition: color 0.2s ease, background-color 0.2s ease; display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; }
        .footer-icon-button:hover { color: var(--primary-dark); background-color: var(--primary-bg); }
        .message-input { flex-grow: 1; padding: 10px 15px; border: 1px solid var(--input-border-color); border-radius: 20px; resize: none; font-size: 0.95rem; line-height: 1.4; min-height: 42px; max-height: 120px; overflow-y: auto; font-family: var(--chat-font-family); color: var(--text-dark); }
        .message-input::placeholder { color: var(--text-light); }
        .message-input:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px var(--input-focus-ring); }
        .send-button { padding: 0; width: 42px; height: 42px; background-color: var(--primary-color); color: white; border: none; border-radius: 50%; cursor: pointer; font-size: 1.1rem; transition: background-color 0.2s ease; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .send-button:hover { background-color: var(--primary-dark); }
        .send-button i { line-height: 1; }

        /* --- Collapsed Chat Icon --- */
        #chat-icon-collapsed { position: fixed; bottom: 20px; right: 20px; width: 60px; height: 60px; background-color: var(--primary-color); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; cursor: grab; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); z-index: 1001; user-select: none; transition: background-color 0.2s ease; }
        #chat-icon-collapsed:hover { background-color: var(--primary-dark); }
        #chat-icon-collapsed:active { cursor: grabbing; }

    </style>
</head>
<body>

    <div id="chat-container" class="chat-container collapsed">

        <div class="chat-header">
            <h3>Live Chat</h3>
            <div class="header-buttons">
                <button id="collapse-chat-btn" class="header-icon-btn" title="Minimize Chat">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>

        <div class="chat-body">
            <div id="contact-list" class="contact-list">
                 <div class="search-container">
                    <input type="text" id="contact-search" placeholder="Search contacts or dept...">
                </div>
                 <ul id="contact-list-ul">
                     <li class="contact-item active" data-department="Sales"> <div class="profile-pic-container"> <img src="https://placehold.co/40x40/A7F3D0/1F2937?text=AS" alt="Alice Smith" class="profile-pic"> <span class="status-indicator online"></span> </div> <div class="contact-info"> <div class="contact-name">Alice Smith</div> <div class="contact-preview">Okay, sounds good!</div> </div> </li>
                     <li class="contact-item" data-department="Tech"> <div class="profile-pic-container"> <img src="https://placehold.co/40x40/FBCFE8/1F2937?text=BD" alt="Bob Doe" class="profile-pic"> <span class="status-indicator idle"></span> </div> <div class="contact-info"> <div class="contact-name">Bob Doe</div> <div class="contact-preview">Can we talk later?</div> </div> </li>
                     <li class="contact-item" data-department="Support"> <div class="profile-pic-container"> <img src="https://placehold.co/40x40/BFDBFE/1F2937?text=CJ" alt="Charlie Jr." class="profile-pic"> <span class="status-indicator busy-call"></span> </div> <div class="contact-info"> <div class="contact-name">Charlie Jr.</div> <div class="contact-preview">On a call...</div> </div> </li>
                     <li class="contact-item" data-department="Sales"> <div class="profile-pic-container"> <img src="https://placehold.co/40x40/E5E7EB/1F2937?text=DM" alt="David Miller" class="profile-pic"> <span class="status-indicator offline"></span> </div> <div class="contact-info"> <div class="contact-name">David Miller</div> <div class="contact-preview">See you monday!</div> </div> </li>
                     <li class="contact-item" data-department="Tech"> <div class="profile-pic-container"> <img src="https://placehold.co/40x40/FECACA/1F2937?text=ET" alt="Eva Taylor" class="profile-pic"> <span class="status-indicator busy-task"></span> </div> <div class="contact-info"> <div class="contact-name">Eva Taylor</div> <div class="contact-preview">Working on the report.</div> </div> </li>
                </ul>
            </div>

            <div class="message-area message-area-dropzone">
                 <div class="message-area-header">
                    <span class="chat-partner-name">Alice Smith</span>
                    <div class="message-area-actions">
                         <div id="message-search-container">
                            <input type="text" id="message-search-input" placeholder="Search messages...">
                            <button class="search-nav-btn" title="Previous Match">&lt;</button>
                            <button class="search-nav-btn" title="Next Match">&gt;</button>
                        </div>
                         <div id="chat-options-menu">
                             <button id="chat-search-btn" class="message-action-btn" title="Search Chat History">
                                 <i class="fas fa-search"></i>
                             </button>
                             <button id="view-attachments-btn" class="message-action-btn" title="View Attachments">
                                 <i class="fas fa-paperclip"></i>
                             </button>
                         </div>
                         <button id="chat-options-toggle-btn" class="message-action-btn" title="More options">
                             <i class="fas fa-plus"></i>
                         </button>
                    </div>
                </div>

                <div id="message-list" class="message-list">
                    <div class="message-system">Chat with Alice Smith</div>
                    <div class="message message-received">Hey! Did you see the latest report?</div>
                    <div class="message message-sent">Yes, looking at it now. The figures for Q2 seem off.</div>
                    <div class="message message-received">I thought so too! Let's schedule a meeting tomorrow at 10:00 AM to discuss.</div>
                    <div class="message message-system">Today - 10:30 AM</div>
                    <div class="message message-sent">Okay, 10 AM works. Please send the invite. Also, can you do the reconciliation task before then?</div>
                </div>

                <div class="chat-footer">
                    <button id="attach-button" class="footer-icon-button" title="Attach File"> <i class="fas fa-paperclip"></i> </button>
                    <button id="call-button" class="footer-icon-button" title="Start Call"> <i class="fas fa-phone"></i> </button>
                    <textarea id="message-input" class="message-input" placeholder="Type your message..." rows="1"></textarea>
                    <button id="send-button" class="send-button" title="Send Message"> <i class="fas fa-paper-plane"></i> </button>
                </div>
            </div> </div> </div> <div id="chat-icon-collapsed" style="display: flex;" title="Open Chat">
        <i class="fas fa-comment-dots"></i>
    </div>


    <script>
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
            const messageArea = document.querySelector('.message-area'); // For drop zone
            const messageList = document.getElementById('message-list'); // For displaying messages
            const messageInput = document.getElementById('message-input'); // Message text input
            const sendButton = document.getElementById('send-button'); // Send button
            const partnerNameEl = document.querySelector('.message-area-header .chat-partner-name'); // Chat partner name element

            // --- Check Essential Elements ---
            // (Add checks for new elements if needed)

            // --- Initial State ---
            // Chat starts collapsed via class in HTML

            // --- Collapse/Expand Logic ---
            if(collapseButton && chatContainer && collapsedIcon) {
                collapseButton.addEventListener('click', () => {
                    chatContainer.classList.add('collapsed');
                    collapsedIcon.style.display = 'flex'; // Show icon immediately
                });
            }

            // --- Dragging Logic (Modified for Click vs Drag) ---
            let isDragging = false; let hasDragged = false; let startX, startY; let iconStartX, iconStartY;
            if(collapsedIcon && chatContainer) {
                collapsedIcon.addEventListener('mousedown', (e) => { if (e.button !== 0) return; isDragging = true; hasDragged = false; startX = e.clientX; startY = e.clientY; iconStartX = collapsedIcon.offsetLeft; iconStartY = collapsedIcon.offsetTop; collapsedIcon.style.cursor = 'grabbing'; e.preventDefault(); });
                collapsedIcon.addEventListener('mouseup', (e) => {
                    if (e.button !== 0) return;
                    if (isDragging && !hasDragged) { // Expand only if NOT dragged
                        chatContainer.style.display = 'flex'; // Make visible before transition
                        requestAnimationFrame(() => { // Ensure display is applied
                            chatContainer.classList.remove('collapsed');
                            collapsedIcon.style.display = 'none'; // Hide icon after expand starts
                        });
                    }
                    // Reset flags even if it was a drag that just ended
                    isDragging = false;
                    hasDragged = false;
                    collapsedIcon.style.cursor = 'grab';
                });
            }
            // Mousemove listener remains the same
            document.addEventListener('mousemove', (e) => { if (!isDragging) return; const currentX = e.clientX; const currentY = e.clientY; const deltaX = currentX - startX; const deltaY = currentY - startY; if (Math.abs(deltaX) > 5 || Math.abs(deltaY) > 5) { hasDragged = true; } let newX = iconStartX + deltaX; let newY = iconStartY + deltaY; const viewportWidth = window.innerWidth; const viewportHeight = window.innerHeight; const iconWidth = collapsedIcon.offsetWidth; const iconHeight = collapsedIcon.offsetHeight; newX = Math.max(0, Math.min(newX, viewportWidth - iconWidth)); newY = Math.max(0, Math.min(newY, viewportHeight - iconHeight)); collapsedIcon.style.left = `${newX}px`; collapsedIcon.style.top = `${newY}px`; collapsedIcon.style.bottom = 'auto'; collapsedIcon.style.right = 'auto'; });
            // Document mouseup listener remains the same (resets state if mouse released outside icon)
            document.addEventListener('mouseup', (e) => { if (e.button !== 0) return; if (isDragging) { isDragging = false; hasDragged = false; if(collapsedIcon) collapsedIcon.style.cursor = 'grab'; } });
            // Document mouseleave listener remains the same
            document.addEventListener('mouseleave', () => { if (isDragging) { isDragging = false; hasDragged = false; if(collapsedIcon) collapsedIcon.style.cursor = 'grab'; } });

             // --- Contact Search/Filter Logic ---
             if(contactSearchInput && contactItems.length > 0) {
                 contactSearchInput.addEventListener('input', (e) => {
                     const searchTerm = e.target.value.toLowerCase().trim();
                     contactItems.forEach(item => {
                         const nameElement = item.querySelector('.contact-name');
                         const department = item.dataset.department?.toLowerCase() || '';
                         const name = nameElement ? nameElement.textContent.toLowerCase() : '';
                         const isMatch = searchTerm === '' || name.includes(searchTerm) || department.includes(searchTerm);
                         if (isMatch) { item.classList.remove('hidden'); } else { item.classList.add('hidden'); }
                     });
                 });
             }

             // --- Contact Click Logic ---
             if (contactItems.length > 0 && partnerNameEl && messageList) {
                 contactItems.forEach(item => {
                     item.addEventListener('click', () => {
                         contactItems.forEach(i => i.classList.remove('active'));
                         item.classList.add('active');
                         const nameElement = item.querySelector('.contact-name');
                         if (nameElement) {
                             partnerNameEl.textContent = nameElement.textContent;
                         }
                         messageList.innerHTML = ''; // Clear previous messages
                         displaySystemMessage(`Chat started with ${nameElement ? nameElement.textContent : 'user'}`);
                         console.log(`Clicked contact: ${nameElement ? nameElement.textContent : 'Unknown'}`);
                         // TODO: Fetch chat history
                     });
                 });
             }

             // --- Chat Options Toggle Logic ---
             if (optionsToggleButton && optionsMenu) {
                 optionsToggleButton.addEventListener('click', (e) => {
                     e.stopPropagation();
                     const isActive = optionsMenu.classList.toggle('active');
                     optionsToggleButton.classList.toggle('active');
                     const icon = optionsToggleButton.querySelector('i');
                     if (isActive) { icon.classList.remove('fa-plus'); icon.classList.add('fa-times'); optionsToggleButton.title = "Close Options"; if (messageSearchContainer) messageSearchContainer.classList.remove('active'); }
                     else { icon.classList.remove('fa-times'); icon.classList.add('fa-plus'); optionsToggleButton.title = "More Options"; if (messageSearchContainer) messageSearchContainer.classList.remove('active'); }
                 });
             }

             // --- Message Search Toggle Logic ---
             if (chatSearchButton && messageSearchContainer && messageSearchInput) {
                 chatSearchButton.addEventListener('click', (e) => {
                     e.stopPropagation();
                     messageSearchContainer.classList.toggle('active');
                     if (messageSearchContainer.classList.contains('active')) {
                         messageSearchInput.focus();
                         if(optionsMenu) optionsMenu.classList.remove('active');
                         if(optionsToggleButton) { optionsToggleButton.classList.remove('active'); const icon = optionsToggleButton.querySelector('i'); icon.classList.remove('fa-times'); icon.classList.add('fa-plus'); optionsToggleButton.title = "More Options"; }
                     }
                 });
             }

             // --- Placeholder for Attachments Button ---
             if (viewAttachmentsButton) {
                 viewAttachmentsButton.addEventListener('click', (e) => {
                     e.stopPropagation();
                     alert("Attachment viewing not implemented yet.");
                     if(optionsMenu) optionsMenu.classList.remove('active');
                     if(optionsToggleButton) { optionsToggleButton.classList.remove('active'); const icon = optionsToggleButton.querySelector('i'); icon.classList.remove('fa-times'); icon.classList.add('fa-plus'); optionsToggleButton.title = "More Options"; }
                 });
             }

             // --- Close Menus on Outside Click ---
             document.addEventListener('click', (e) => {
                 if (optionsMenu && optionsMenu.classList.contains('active') && !optionsMenu.contains(e.target) && !optionsToggleButton.contains(e.target)) {
                     optionsMenu.classList.remove('active'); optionsToggleButton.classList.remove('active'); const icon = optionsToggleButton.querySelector('i'); icon.classList.remove('fa-times'); icon.classList.add('fa-plus'); optionsToggleButton.title = "More Options";
                 }
                 if (messageSearchContainer && messageSearchContainer.classList.contains('active') && !messageSearchContainer.contains(e.target) && !chatSearchButton?.contains(e.target)) {
                     if (!optionsToggleButton || !optionsToggleButton.contains(e.target)) { messageSearchContainer.classList.remove('active'); }
                 }
             });

             // --- Placeholder for Message Search Input ---
             if (messageSearchInput) {
                 messageSearchInput.addEventListener('input', (e) => { console.log("Searching messages for:", e.target.value); });
                 messageSearchInput.addEventListener('keypress', (e) => { if (e.key === 'Enter') { console.log("Perform search for:", e.target.value); } });
             }

             // --- Helper Functions ---
             function displayMessage(text, typeClass = 'message-received') { /* ... same ... */ }
             function displaySystemMessage(text) { /* ... same ... */ }
             function scrollToBottom() { /* ... same ... */ }

             // --- Send Message Logic (Modified for UI Feedback) ---
             function handleSendMessage() {
                 if (!messageInput) return;
                 const messageText = messageInput.value.trim();
                 if (messageText) {
                     // 1. Display message immediately
                     displayMessage(messageText, 'message-sent');
                     // 2. Clear input
                     messageInput.value = '';
                     messageInput.focus();
                     // 3. Attempt to send via WebSocket (placeholder)
                     console.warn('WebSocket sending not implemented in preview.');
                 }
             }
             if (sendButton) { sendButton.addEventListener('click', handleSendMessage); }
             if (messageInput) { messageInput.addEventListener('keypress', (event) => { if (event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); handleSendMessage(); } }); }

            // --- Drag and Drop File Logic ---
            const dropZones = [messageArea, ...contactItems];
            dropZones.forEach(zone => {
                if (!zone) return;
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => { zone.addEventListener(eventName, (e) => { e.preventDefault(); e.stopPropagation(); }, false); });
                ['dragenter', 'dragover'].forEach(eventName => { zone.addEventListener(eventName, () => { zone.classList.add('dragover'); }, false); });
                ['dragleave', 'drop'].forEach(eventName => { zone.addEventListener(eventName, () => { zone.classList.remove('dragover'); }, false); });
                zone.addEventListener('drop', (e) => {
                    const droppedFiles = e.dataTransfer?.files;
                    if (droppedFiles && droppedFiles.length > 0) {
                        let targetInfo = "in the chat area";
                        if (zone.classList.contains('contact-item')) {
                            const nameEl = zone.querySelector('.contact-name');
                            targetInfo = `on contact ${nameEl ? nameEl.textContent : 'Unknown'}`;
                            // Simulate clicking the contact to switch view
                            zone.click();
                        }
                        Array.from(droppedFiles).forEach(file => {
                            console.log(`File dropped ${targetInfo}:`, file.name, file.type, file.size);
                            alert(`Dropped file "${file.name}" ${targetInfo}.\n(File handling not implemented in preview)`);
                            // TODO: Initiate upload
                        });
                    }
                }, false);
            });

             // --- WebSocket Connection Logic (Placeholder) ---
             let socket; function connectWebSocket() { /* ... same placeholder ... */ }
             // connectWebSocket(); // Keep commented out for preview

        }); // End DOMContentLoaded
    </script>

</body>
</html>
