// dashboard.js

document.addEventListener('DOMContentLoaded', () => {
    // --- DOM Elements ---
    const sidebar = document.querySelector('.sidebar');
    const mobileMenuButton = document.querySelector('header button[aria-label="Open sidebar"]'); // Added
    const statsSection = document.getElementById('stats-section');
    const departmentsList = document.getElementById('departments-list');
    const globalSearchInput = document.getElementById('global-search-input');
    const fabAddCandidate = document.getElementById('fab-add-candidate');

    // Stat Card Click Handlers (using specific IDs)
    const viewScreeningCard = document.getElementById('view-screening-card');
    const viewInterviewQueueCard = document.getElementById('view-interview-queue-card');
    const viewOnboardingCard = document.getElementById('view-onboarding-card');
    const viewEmployeesCard = document.getElementById('view-employees-card');

    // Modal Elements
    const modal = document.getElementById('employee-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalBody = document.getElementById('modal-body');
    const modalFooter = document.getElementById('modal-footer');
    const modalCloseButton = document.getElementById('modal-close-button');
    const modalCancelButton = document.getElementById('modal-cancel-button');
    const modalSaveButton = document.getElementById('modal-save-button');
    const modalAddCandidateSaveButton = document.getElementById('modal-add-candidate-save-button');
    const modalLoader = document.getElementById('modal-loader');
    const modalDefaultText = document.getElementById('modal-default-text');
    let currentEditingEmployeeId = null; // Keep track of employee being edited

    // Header Buttons (Placeholders for functionality)
    const notificationsButton = document.getElementById('notifications-button');
    const guideButton = document.getElementById('guide-button');
    const themeToggleButton = document.getElementById('theme-toggle-button');
    const settingsButton = document.getElementById('settings-button');
    const profileButton = document.getElementById('profile-button');


    // --- API Endpoint ---
    const API_URL = 'api.php';

    // --- Helper Functions ---
    const fetchData = async (params) => {
        const url = `${API_URL}?${new URLSearchParams(params)}`;
        showLoader(modalLoader); // Show modal loader for most fetches triggering modal content
        try {
            const response = await fetch(url);
            if (!response.ok) {
                let errorMsg = `HTTP error! Status: ${response.status}`;
                try { // Try to get more specific error from API response
                    const errorData = await response.json();
                    errorMsg = errorData.message || errorMsg;
                } catch (e) { /* Ignore if response isn't JSON */ }
                throw new Error(errorMsg);
            }
            const data = await response.json();
            // Don't hide loader here, let the calling function hide it after rendering
            return data;
        } catch (error) {
            console.error('Fetch Error:', error);
            displayModalError(`Error fetching data: ${error.message}`); // Display error in modal
            hideLoader(modalLoader);
            return { success: false, message: error.message };
        }
    };

    // Updated postData to handle FormData (for file uploads) or JSON
    const postData = async (url, bodyData, isFormData = false) => {
        // Decide which loader to show, maybe pass it as an argument?
        // For now, using modal loader.
        showLoader(modalLoader);
        try {
            const fetchOptions = {
                method: 'POST',
                headers: {}, // Headers set differently for FormData vs JSON
                body: bodyData
            };

            if (!isFormData) {
                fetchOptions.headers['Content-Type'] = 'application/json';
                fetchOptions.body = JSON.stringify(bodyData);
            }
            // For FormData, 'Content-Type' is set automatically by the browser with boundary

            // Add CSRF token header if implemented
            // fetchOptions.headers['X-CSRF-Token'] = 'YOUR_CSRF_TOKEN';

            const response = await fetch(url, fetchOptions);

            // Always attempt to parse JSON, as API should return JSON even for errors
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || `HTTP error! Status: ${response.status}`);
            }

            hideLoader(modalLoader);
            return data; // Should include { success: true/false, ... }
        } catch (error) {
            console.error('Post Error:', error);
            // Display error, perhaps in the modal form
            displayModalError(`Error saving data: ${error.message}`);
            hideLoader(modalLoader);
            return { success: false, message: error.message };
        }
    };


    const showLoader = (loaderElement) => {
        loaderElement?.classList.remove('hidden');
        if (loaderElement === modalLoader && modalDefaultText) {
             modalDefaultText.classList.add('hidden'); // Hide default text when loader shows
        }
    };

    const hideLoader = (loaderElement) => {
         loaderElement?.classList.add('hidden');
         if (loaderElement === modalLoader && modalDefaultText && modalBody.childElementCount <= 2 ) { // Only show default if no other content + loader
             // This logic might need refinement based on how content is cleared/rendered
             // modalDefaultText.classList.remove('hidden');
         }
    };

    // Display errors within the modal body
    const displayModalError = (message) => {
        if(modalBody) {
            // Clear previous content before showing error? Or append? Let's replace for now.
            modalBody.innerHTML = `<p class="text-red-600 p-4 bg-red-50 rounded-md">${message}</p>`;
        }
        hideLoader(modalLoader); // Ensure loader is hidden on error
    };

     // Display errors within a specific form inside the modal
    const displayModalFormError = (formId, message) => {
        const errorContainer = document.querySelector(`#${formId} #modal-form-errors`); // Assuming an error div exists in the form
         if(errorContainer) {
            errorContainer.textContent = message;
            errorContainer.classList.remove('hidden');
         } else {
            // Fallback if no specific error container
            alert(message);
         }
    };
     const clearModalFormError = (formId) => {
        const errorContainer = document.querySelector(`#${formId} #modal-form-errors`);
         if(errorContainer) {
            errorContainer.textContent = '';
            errorContainer.classList.add('hidden');
         }
    };


    // --- Load Initial Data ---
    const loadDashboardData = async () => {
        // Load stats and departments
        // Using sequential awaits for simplicity here, parallel is also possible
        const statsData = await fetchData({ action: 'get_stats' });
        if (statsData.success) renderStats(statsData.data);
        // Reset loader state after stats rendering (fetchData doesn't hide it)
        hideLoader(modalLoader);

        const deptsData = await fetchData({ action: 'get_departments' });
        if (deptsData.success) renderDepartments(deptsData.data);
        else displayError(departmentsList, deptsData.message || 'Could not load departments.');
        // Reset loader state after departments rendering
        hideLoader(modalLoader);

        // Initial rendering of icons
        lucide.createIcons();
    };

    // --- Rendering Functions ---
    const renderStats = (stats) => {
        // Update the numbers in the existing stat cards
        // Assumes the order in HTML matches: Screening, Interview, Onboarding, Employees
        const statValues = [
            stats.screening ?? 'N/A',
            stats.interview ?? 'N/A',
            stats.onboarding ?? 'N/A',
            stats.employees ?? 'N/A'
        ];
        const cards = statsSection.querySelectorAll('.bg-white'); // Get all card divs
        if (cards.length === statValues.length) {
            cards.forEach((card, index) => {
                const valueElement = card.querySelector('.text-3xl');
                if (valueElement) {
                    valueElement.textContent = statValues[index];
                }
            });
        } else {
            console.error("Mismatch between expected stat cards and found elements.");
            // Optionally reload the whole section if structure might change drastically
        }
    };

    const renderDepartments = (departments) => {
        if (!departmentsList) return;
        if (!departments || departments.length === 0) {
            departmentsList.innerHTML = '<p class="text-gray-500 text-sm px-4">No active departments found.</p>';
            return;
        }
        departmentsList.innerHTML = departments.map(dept => `
            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <button data-department="${dept.department}" class="department-button w-full text-left flex justify-between items-center p-4 hover:bg-gray-50 text-gray-700 transition duration-150 ease-in-out" aria-expanded="false">
                    <span class="flex items-center">
                        <i data-lucide="building-2" class="mr-3 h-5 w-5 text-gray-400"></i> <span class="font-medium">${dept.department || 'Unassigned'}</span>
                    </span>
                    <div class="flex items-center space-x-3">
                        <span class="text-sm bg-blue-100 text-blue-800 font-semibold px-2.5 py-0.5 rounded-full">${dept.count}</span>
                        <i data-lucide="chevron-down" class="h-5 w-5 text-gray-400 transition-transform duration-300"></i>
                    </div>
                </button>
                <div class="department-employee-list px-4 pb-4 border-t border-gray-200 bg-white max-h-[300px] overflow-y-auto custom-scrollbar">
                    <div id="dept-${dept.department?.replace(/\s+/g, '-')}-loader" class="hidden loader !w-5 !h-5 !border-2 !my-2"></div>
                    <ul class="employee-list-ul text-sm space-y-2 pt-3"></ul>
                </div>
            </div>
        `).join('');

        // Re-render icons and add event listeners
        lucide.createIcons();
        addDepartmentListeners();
    };

    const renderEmployeeListForDepartment = (container, employees) => {
        const listUl = container.querySelector('.employee-list-ul');
        if (!listUl) return;

        if (!employees || employees.length === 0) {
            listUl.innerHTML = '<li class="text-gray-500 italic text-xs py-2">No employees found in this department.</li>';
            return;
        }

        listUl.innerHTML = employees.map(emp => `
            <li class="flex justify-between items-center group py-1 hover:bg-gray-50 px-1 rounded">
                <div>
                    <span class="font-medium text-gray-800">${emp.full_name}</span>
                    <span class="text-xs text-gray-500 block">${emp.position || 'N/A'}</span>
                </div>
                <span class="hidden group-hover:flex items-center space-x-2">
                    <button data-id="${emp.id}" class="view-employee-button text-blue-500 hover:text-blue-700" title="View"><i data-lucide="eye" class="h-4 w-4"></i></button>
                    <button data-id="${emp.id}" class="edit-employee-button text-amber-500 hover:text-amber-700" title="Edit"><i data-lucide="pencil" class="h-4 w-4"></i></button>
                </span>
            </li>
        `).join('');

        // Add listeners for the newly added buttons
        container.querySelectorAll('.view-employee-button').forEach(btn => btn.addEventListener('click', (e) => {
            e.stopPropagation(); // Prevent department toggle
            openModalForView(btn.dataset.id);
        }));
        container.querySelectorAll('.edit-employee-button').forEach(btn => btn.addEventListener('click', (e) => {
             e.stopPropagation(); // Prevent department toggle
            openModalForEdit(btn.dataset.id);
        }));
        lucide.createIcons();
    };


    // --- Modal Content Rendering Functions ---

    const renderModalViewEmployee = (employee) => {
        // Renders the detailed view of an employee in the modal
        modalBody.innerHTML = `
            <dl class="space-y-3 text-sm">
                 ${employee.image_path ? `<div class="flex justify-center mb-4"><img src="${employee.image_path}" alt="Photo" class="h-24 w-24 rounded-full object-cover border shadow-sm"></div>` : ''}
                <div class="grid grid-cols-3 gap-2 border-b pb-2"> <dt class="font-semibold text-gray-600 col-span-1">Full Name:</dt> <dd class="text-gray-900 col-span-2">${employee.full_name || 'N/A'}</dd> </div>
                <div class="grid grid-cols-3 gap-2 border-b pb-2"> <dt class="font-semibold text-gray-600 col-span-1">Employee #:</dt> <dd class="text-gray-900 col-span-2">${employee.employee_number || 'N/A'}</dd> </div>
                <div class="grid grid-cols-3 gap-2 border-b pb-2"> <dt class="font-semibold text-gray-600 col-span-1">Position:</dt> <dd class="text-gray-900 col-span-2">${employee.position || 'N/A'}</dd> </div>
                <div class="grid grid-cols-3 gap-2 border-b pb-2"> <dt class="font-semibold text-gray-600 col-span-1">Designation:</dt> <dd class="text-gray-900 col-span-2">${employee.designation || 'N/A'}</dd> </div>
                <div class="grid grid-cols-3 gap-2 border-b pb-2"> <dt class="font-semibold text-gray-600 col-span-1">Department:</dt> <dd class="text-gray-900 col-span-2">${employee.department || 'N/A'}</dd> </div>
                <div class="grid grid-cols-3 gap-2 border-b pb-2"> <dt class="font-semibold text-gray-600 col-span-1">Manager:</dt> <dd class="text-gray-900 col-span-2">${employee.manager_name || 'N/A'} ${employee.manager_id ? `(ID: ${employee.manager_id})` : ''}</dd> </div>
                <div class="grid grid-cols-3 gap-2 border-b pb-2"> <dt class="font-semibold text-gray-600 col-span-1">Joining Date:</dt> <dd class="text-gray-900 col-span-2">${employee.joining_date || 'N/A'}</dd> </div>
                <div class="grid grid-cols-3 gap-2 border-b pb-2"> <dt class="font-semibold text-gray-600 col-span-1">Office Email:</dt> <dd class="text-gray-900 col-span-2">${employee.office_email || 'N/A'}</dd> </div>
                <div class="grid grid-cols-3 gap-2 border-b pb-2"> <dt class="font-semibold text-gray-600 col-span-1">Mobile:</dt> <dd class="text-gray-900 col-span-2">${employee.mobile_number || 'N/A'}</dd> </div>
                <div class="grid grid-cols-3 gap-2 border-b pb-2"> <dt class="font-semibold text-gray-600 col-span-1">Status:</dt> <dd class="text-gray-900 col-span-2 capitalize">${employee.status || 'N/A'}</dd> </div>
                <div class="mt-4 pt-3 border-t">
                     <h5 class="font-semibold text-gray-600 mb-2 text-base">Documents</h5>
                     <p class="text-xs text-gray-500 italic">(Document list placeholder - requires implementation)</p>
                     </div>
                 <div class="mt-4 pt-3 border-t">
                     <h5 class="font-semibold text-gray-600 mb-2 text-base">Performance History</h5>
                     <p class="text-xs text-gray-500 italic">(Performance history placeholder - requires implementation)</p>
                     </div>
            </dl>
        `;
        hideLoader(modalLoader);
        modalDefaultText.classList.add('hidden');
        modalFooter.classList.add('hidden'); // No footer for view
    };

    const renderModalEditForm = (employee) => {
        // Renders the edit form in the modal, pre-filled
        // IMPORTANT: Include ALL fields you want editable
         modalBody.innerHTML = `
            <form id="edit-employee-form" class="space-y-4 text-sm">
                 <input type="hidden" name="id" value="${employee.id}">
                 <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                     <div>
                         <label for="edit-full_name" class="block font-medium text-gray-700">Full Name *</label>
                         <input type="text" id="edit-full_name" name="full_name" value="${employee.full_name || ''}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                     </div>
                     <div>
                         <label for="edit-position" class="block font-medium text-gray-700">Position</label>
                         <input type="text" id="edit-position" name="position" value="${employee.position || ''}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                     </div>
                      <div>
                         <label for="edit-department" class="block font-medium text-gray-700">Department</label>
                         <input type="text" id="edit-department" name="department" value="${employee.department || ''}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                     </div>
                     <div>
                         <label for="edit-office_email" class="block font-medium text-gray-700">Office Email *</label>
                         <input type="email" id="edit-office_email" name="office_email" value="${employee.office_email || ''}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                     </div>
                     <div>
                         <label for="edit-mobile_number" class="block font-medium text-gray-700">Mobile Number</label>
                         <input type="tel" id="edit-mobile_number" name="mobile_number" value="${employee.mobile_number || ''}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                     </div>
                     <div>
                         <label for="edit-status" class="block font-medium text-gray-700">Status</label>
                         <select id="edit-status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <option value="candidate" ${employee.status === 'candidate' ? 'selected' : ''}>Candidate</option>
                            <option value="onboarding" ${employee.status === 'onboarding' ? 'selected' : ''}>Onboarding</option>
                            <option value="active" ${employee.status === 'active' ? 'selected' : ''}>Active</option>
                            <option value="inactive" ${employee.status === 'inactive' ? 'selected' : ''}>Inactive</option>
                            <option value="screening_failed" ${employee.status === 'screening_failed' ? 'selected' : ''}>Screening Failed</option>
                            <option value="interview_failed" ${employee.status === 'interview_failed' ? 'selected' : ''}>Interview Failed</option>
                         </select>
                     </div>
                      </div>
                 <div id="modal-form-errors" class="text-red-500 text-sm mt-2 hidden"></div>
            </form>
        `;
        hideLoader(modalLoader);
        modalDefaultText.classList.add('hidden');
        // Show appropriate footer buttons
        modalFooter.classList.remove('hidden');
        modalSaveButton.classList.remove('hidden');
        modalAddCandidateSaveButton.classList.add('hidden');
    };

     const renderModalAddCandidateForm = () => {
        // Renders the form for adding a new candidate
         modalBody.innerHTML = `
            <form id="add-candidate-form" class="space-y-4 text-sm" enctype="multipart/form-data"> <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="cand-name" class="block font-medium text-gray-700">Full Name *</label>
                        <input type="text" id="cand-name" name="full_name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label for="cand-fname" class="block font-medium text-gray-700">Father's Name</label>
                        <input type="text" id="cand-fname" name="father_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label for="cand-cnic" class="block font-medium text-gray-700">CNIC Number</label>
                        <input type="text" id="cand-cnic" name="cnic" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                     <div>
                        <label for="cand-mobile" class="block font-medium text-gray-700">Mobile Number *</label>
                        <input type="tel" id="cand-mobile" name="mobile_number" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                     <div class="md:col-span-2">
                        <label for="cand-address" class="block font-medium text-gray-700">Address</label>
                        <textarea id="cand-address" name="address" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                    </div>
                     <div>
                        <label for="cand-dob" class="block font-medium text-gray-700">Date of Birth</label>
                        <input type="date" id="cand-dob" name="dob" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                     <div>
                        <label for="cand-marital" class="block font-medium text-gray-700">Marital Status</label>
                        <select id="cand-marital" name="marital_status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">Select...</option>
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                     <div class="md:col-span-2">
                        <label for="cand-education" class="block font-medium text-gray-700">Education</label>
                        <input type="text" id="cand-education" name="education" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                     <div class="md:col-span-2">
                        <label for="cand-image" class="block font-medium text-gray-700">Candidate Image</label>
                        <input type="file" id="cand-image" name="candidate_image" accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                </div>
                <div id="modal-form-errors" class="text-red-500 text-sm mt-2 hidden"></div>
            </form>
        `;
        hideLoader(modalLoader);
        modalDefaultText.classList.add('hidden');
        // Show appropriate footer buttons
        modalFooter.classList.remove('hidden');
        modalSaveButton.classList.add('hidden');
        modalAddCandidateSaveButton.classList.remove('hidden');
     };

     const renderModalCandidateList = (title, candidates, stage) => {
         // Renders lists for Screening, Interview, Onboarding
         let listHtml = '<p class="text-gray-500 italic text-sm">No candidates found in this stage.</p>';
         if (candidates && candidates.length > 0) {
             listHtml = candidates.map(c => {
                 let actionsHtml = '';
                 // Add specific actions based on stage
                 if (stage === 'screening') {
                     actionsHtml = `
                        <button data-id="${c.id}" data-action="pass_screening" class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded hover:bg-green-200 candidate-action-button">Pass</button>
                        <button data-id="${c.id}" data-action="fail_screening" class="text-xs bg-red-100 text-red-700 px-2 py-1 rounded hover:bg-red-200 candidate-action-button">Fail</button>
                        <button data-id="${c.id}" class="view-employee-button text-xs text-blue-500 hover:underline ml-1">Details</button>
                     `;
                 } else if (stage === 'interview') {
                      actionsHtml = `
                        <button data-id="${c.id}" data-action="pass_interview" class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded hover:bg-green-200 candidate-action-button">Pass</button>
                        <button data-id="${c.id}" data-action="fail_interview" class="text-xs bg-red-100 text-red-700 px-2 py-1 rounded hover:bg-red-200 candidate-action-button">Fail</button>
                        <button data-id="${c.id}" class="view-employee-button text-xs text-blue-500 hover:underline ml-1">Details</button>
                        `;
                 } else if (stage === 'onboarding') {
                      actionsHtml = `
                        <button data-id="${c.id}" data-action="complete_onboarding" class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded hover:bg-green-200 candidate-action-button">Complete</button>
                        <button data-id="${c.id}" class="view-employee-button text-xs text-blue-500 hover:underline ml-1">Details</button>
                        `;
                 }

                 return `
                    <div class="p-3 border rounded bg-gray-50 text-sm flex flex-wrap justify-between items-center gap-2">
                        <div>
                            <p class="font-medium text-gray-800">${c.full_name}</p>
                            <p class="text-xs text-gray-500">${c.position || 'N/A'} - Stage: ${c.interview_level_completed || c.status || 'N/A'}</p>
                        </div>
                        <div class="space-x-2 flex-shrink-0">
                            ${actionsHtml}
                        </div>
                    </div>`;
             }).join('');
         }

         modalBody.innerHTML = `
            <div>
                <h4 class="text-lg font-medium text-gray-700 mb-3">${title} (${candidates?.length ?? 0})</h4>
                <input type="search" placeholder="Filter list..." class="mb-3 w-full text-sm border-gray-200 rounded">
                <div class="space-y-2 max-h-[50vh] overflow-y-auto custom-scrollbar pr-2">
                    ${listHtml}
                </div>
            </div>
         `;
        hideLoader(modalLoader);
        modalDefaultText.classList.add('hidden');
        modalFooter.classList.add('hidden'); // Usually no footer needed for list views, actions are inline

        // Add listeners for inline view buttons and action buttons
        modalBody.querySelectorAll('.view-employee-button').forEach(btn => btn.addEventListener('click', () => openModalForView(btn.dataset.id)));
        modalBody.querySelectorAll('.candidate-action-button').forEach(btn => btn.addEventListener('click', handleCandidateAction));

     };

     const renderModalActiveEmployeeList = (employees) => {
         // Renders the table for active employees
          let tableHtml = '<p class="text-gray-500 italic text-sm">No active employees found.</p>';
          if (employees && employees.length > 0) {
              tableHtml = `
                <div class="overflow-x-auto">
                    <table class="min-w-full modal-table">
                        <thead>
                            <tr>
                                <th></th> <th>Emp #</th>
                                <th>Name</th>
                                <th>Designation</th>
                                <th>Department</th>
                                <th>Reporting To</th>
                                <th>Mobile</th>
                                <th>Email</th>
                                <th></th> </tr>
                        </thead>
                        <tbody>
                            ${employees.map(emp => `
                                <tr>
                                    <td>
                                        ${emp.image_path ?
                                            `<img src="${emp.image_path}" alt="${emp.full_name}" class="h-9 w-9 rounded-full object-cover">` :
                                            `<span class="h-9 w-9 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 text-xs font-semibold">${emp.full_name ? emp.full_name.slice(0, 2).toUpperCase() : '?'}</span>`
                                        }
                                    </td>
                                    <td><span class="emp-no">${emp.employee_number || 'N/A'}</span></td>
                                    <td class="font-medium">${emp.full_name}</td>
                                    <td>${emp.designation || 'N/A'}</td>
                                    <td>${emp.department || 'N/A'}</td>
                                    <td>${emp.manager_name || 'N/A'}</td>
                                    <td>${emp.mobile_number || 'N/A'}</td>
                                    <td>${emp.office_email || 'N/A'}</td>
                                    <td class="space-x-2 whitespace-nowrap">
                                        <button data-id="${emp.id}" class="view-employee-button text-blue-500 hover:underline text-xs" title="View Details">View</button>
                                        <button data-id="${emp.id}" class="edit-employee-button text-amber-500 hover:underline text-xs" title="Edit Details">Edit</button>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
                `;
          }

          modalBody.innerHTML = `
            <div>
                <h4 class="text-lg font-medium text-gray-700 mb-3">Active Employees (${employees?.length ?? 0})</h4>
                <input type="search" placeholder="Filter active employees..." class="mb-3 w-full text-sm border-gray-200 rounded">
                ${tableHtml}
            </div>
          `;
         hideLoader(modalLoader);
         modalDefaultText.classList.add('hidden');
         modalFooter.classList.add('hidden'); // No footer for list view

         // Add listeners for inline view/edit buttons
         modalBody.querySelectorAll('.view-employee-button').forEach(btn => btn.addEventListener('click', () => openModalForView(btn.dataset.id)));
         modalBody.querySelectorAll('.edit-employee-button').forEach(btn => btn.addEventListener('click', () => openModalForEdit(btn.dataset.id)));
     };


    // --- Modal Opening Functions ---
    const openModal = () => {
        modal.classList.remove('hidden');
        document.addEventListener('keydown', closeModalOnEsc);
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        modalTitle.textContent = 'Details'; // Reset title
        modalBody.innerHTML = ''; // Clear body completely
        modalDefaultText.classList.remove('hidden'); // Show default text again
        modalFooter.classList.add('hidden'); // Hide footer
        modalSaveButton.classList.add('hidden');
        modalAddCandidateSaveButton.classList.add('hidden');
        currentEditingEmployeeId = null;
        document.removeEventListener('keydown', closeModalOnEsc);
    };

    const closeModalOnEsc = (event) => {
        if (event.key === 'Escape') {
            closeModal();
        }
    };

    const openModalForView = async (employeeId) => {
        openModal();
        modalTitle.textContent = 'View Employee Details';
        showLoader(modalLoader); // Show loader immediately

        const data = await fetchData({ action: 'get_employee_details', id: employeeId });
        if (data.success && data.data) {
            renderModalViewEmployee(data.data);
        } else {
             displayModalError(data.message || 'Could not load employee details.');
        }
    };

     const openModalForEdit = async (employeeId) => {
        openModal();
        modalTitle.textContent = 'Edit Employee Details';
        showLoader(modalLoader);
        currentEditingEmployeeId = employeeId;

        const data = await fetchData({ action: 'get_employee_details', id: employeeId });
        if (data.success && data.data) {
            renderModalEditForm(data.data);
        } else {
             displayModalError(data.message || 'Could not load employee details for editing.');
             modalFooter.classList.add('hidden');
        }
    };

    const openModalForAddCandidate = () => {
        openModal();
        modalTitle.textContent = 'Add New Candidate';
        renderModalAddCandidateForm(); // Renders the form structure
    };

    const openModalForList = async (listType) => {
        // listType: 'screening', 'interview', 'onboarding', 'active_employees'
        openModal();
        let action = 'get_candidates_by_stage';
        let params = { action: action, stage: listType };
        let title = '';

        switch(listType) {
            case 'screening':
                title = 'Initial Screening Candidates';
                break;
            case 'interview':
                title = 'Interview Queue Candidates';
                break;
            case 'onboarding':
                 title = 'Onboarding Employees';
                 break;
            case 'active_employees':
                 title = 'Active Employees';
                 action = 'get_active_employees'; // Use different API action
                 params = { action: action };
                 break;
            default:
                closeModal(); // Close if type is invalid
                return;
        }

        modalTitle.textContent = title;
        showLoader(modalLoader);

        const data = await fetchData(params);

        if (data.success && data.data) {
            if (listType === 'active_employees') {
                renderModalActiveEmployeeList(data.data);
            } else {
                renderModalCandidateList(title, data.data, listType);
            }
        } else {
             displayModalError(data.message || `Could not load ${listType} list.`);
        }
    };


    // --- Event Handlers ---

    const handleDepartmentToggle = async (button) => {
        const deptName = button.dataset.department;
        const listDiv = button.nextElementSibling; // The .department-employee-list div
        const loader = listDiv.querySelector('.loader');
        const isExpanded = button.getAttribute('aria-expanded') === 'true';

        if (isExpanded) {
            // Collapse
            button.setAttribute('aria-expanded', 'false');
            button.classList.remove('expanded');
            listDiv.classList.remove('expanded');
        } else {
            // Expand
            button.setAttribute('aria-expanded', 'true');
            button.classList.add('expanded');
            listDiv.classList.add('expanded');

            // Load data only if list is empty (or implement refresh logic)
            const listUl = listDiv.querySelector('.employee-list-ul');
            if (listUl && listUl.children.length === 0) {
                showLoader(loader);
                const data = await fetchData({ action: 'get_employees_by_dept', department: deptName });
                hideLoader(loader); // Hide loader from fetchData won't work here
                 hideLoader(loader); // Hide specific dept loader
                if (data.success && data.data) {
                    renderEmployeeListForDepartment(listDiv, data.data);
                } else {
                    displayError(listUl, data.message || `Could not load employees.`);
                }
            }
        }
    };

    const handleSaveChanges = async () => {
        const form = document.getElementById('edit-employee-form');
        if (!form || !currentEditingEmployeeId) return;

        const formData = new FormData(form);
        const dataToSend = Object.fromEntries(formData.entries());
        dataToSend.id = currentEditingEmployeeId;

        clearModalFormError('edit-employee-form'); // Clear previous errors

        // Add basic frontend validation if needed...

        modalSaveButton.disabled = true;
        modalSaveButton.textContent = 'Saving...';

        const result = await postData(`${API_URL}?action=save_employee`, dataToSend);

        modalSaveButton.disabled = false;
        modalSaveButton.textContent = 'Save Changes';

        if (result.success) {
            closeModal();
            // Consider more subtle feedback than alert
            alert(result.message || 'Employee updated successfully!');
            // Refresh relevant parts of the dashboard
            loadDashboardData(); // Reload stats and departments (which might reload lists)
            // If a specific department list was open, maybe reload just that one?
        } else {
            displayModalFormError('edit-employee-form', result.message || 'An unknown error occurred.');
        }
    };

    const handleAddCandidate = async (event) => {
        event.preventDefault(); // Prevent default form submission
        const form = document.getElementById('add-candidate-form');
        if (!form) return;

        // Use FormData to handle file upload correctly
        const formData = new FormData(form);

        clearModalFormError('add-candidate-form');

        // Add basic frontend validation if needed...
        if (!formData.get('full_name') || !formData.get('mobile_number')) {
             displayModalFormError('add-candidate-form', 'Full Name and Mobile Number are required.');
             return;
        }

        modalAddCandidateSaveButton.disabled = true;
        modalAddCandidateSaveButton.textContent = 'Adding...';

        // Post data using FormData
        const result = await postData(`${API_URL}?action=add_candidate`, formData, true); // Pass true for FormData

        modalAddCandidateSaveButton.disabled = false;
        modalAddCandidateSaveButton.textContent = 'Add Candidate';

        if (result.success) {
            closeModal();
            alert(result.message || 'Candidate added successfully!');
            loadDashboardData(); // Refresh stats
        } else {
             displayModalFormError('add-candidate-form', result.message || 'An unknown error occurred.');
        }

    };

    const handleCandidateAction = async (event) => {
        const button = event.target.closest('.candidate-action-button');
        if (!button) return;

        const candidateId = button.dataset.id;
        const action = button.dataset.action; // e.g., 'pass_screening', 'fail_interview'

        if (!candidateId || !action) return;

        // Confirm potentially destructive actions?
        // if (action.includes('fail') || action.includes('reject')) {
        //     if (!confirm('Are you sure you want to mark this candidate as failed/rejected?')) {
        //         return;
        //     }
        // }

        let apiAction = 'update_candidate_stage';
        let payload = { id: candidateId };
        let successMessage = 'Candidate status updated.';

        // Determine new stage/status based on action
        switch(action) {
            case 'pass_screening':
                payload.stage = 'Screening Passed'; // Or 'Ready for Interview'
                // Status remains 'candidate'
                break;
            case 'fail_screening':
                payload.status = 'screening_failed'; // Use specific inactive status
                payload.stage = 'Screening Failed';
                successMessage = 'Candidate marked as failed screening.';
                break;
            case 'pass_interview':
                 payload.stage = 'Interview Passed';
                 // Optionally set status to 'onboarding' directly or require another step/approval
                 // For now, let's assume direct move to onboarding for simplicity
                 payload.status = 'onboarding';
                 successMessage = 'Candidate passed interview and moved to onboarding.';
                break;
            case 'fail_interview':
                 payload.status = 'interview_failed';
                 payload.stage = 'Interview Failed';
                 successMessage = 'Candidate marked as failed interview.';
                break;
             case 'complete_onboarding':
                 paylo