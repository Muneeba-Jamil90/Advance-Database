document.addEventListener('DOMContentLoaded', () => {


    const globalMessageArea = document.createElement('div');
    globalMessageArea.className = 'message-area';
    document.body.prepend(globalMessageArea);

    // --- Navigation Links Scroll ---
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('.nav-link').forEach(nav => nav.classList.remove('active'));
            this.classList.add('active');
            const targetId = this.getAttribute('href').substring(1);
            document.getElementById(targetId).scrollIntoView({ behavior: 'smooth' });
        });
    });

    // --- Handle "View/Manage" Buttons on Data Cards ---
    document.querySelectorAll('.view-manage-btn').forEach(button => {
        button.addEventListener('click', function() {
            const tableType = this.dataset.tableType; // e.g., 'client', 'lawyer'
            const targetManagementAreaId = `${tableType}ManagementArea`; // e.g., 'clientManagementArea'
            const targetManagementArea = document.getElementById(targetManagementAreaId);
            const dynamicTableDisplayArea = document.getElementById('dynamicDataTableDisplay');
            const dynamicTableHeading = dynamicTableDisplayArea.querySelector('.dynamic-table-heading');

            // Hide all other management areas and reset their buttons
            document.querySelectorAll('.data-management-area').forEach(area => {
                if (area.id !== targetManagementAreaId) {
                    area.style.display = 'none';
                    // Hide any forms within other areas
                    const formInOtherArea = area.querySelector('.form-container');
                    if (formInOtherArea) formInOtherArea.style.display = 'none';
                }
            });
            dynamicTableDisplayArea.style.display = 'block'; // Show the main dynamic table display area
            // Toggle the clicked management area
            if (targetManagementArea.style.display === 'none' || targetManagementArea.style.display === '') {
                // Show the specific management area
                targetManagementArea.style.display = 'block';
                dynamicTableHeading.textContent = `${tableType.charAt(0).toUpperCase() + tableType.slice(1)} Records`;

                // Fetch and display the specific table
                const currentDataTableContainer = targetManagementArea.querySelector('.table-container');
                switch (tableType) {
                    case 'client': refreshClients(currentDataTableContainer);
                        break;
                    case 'lawyer': refreshLawyers(currentDataTableContainer); break;
                    case 'case': refreshCases(currentDataTableContainer); break;
                    case 'hearing': refreshHearings(currentDataTableContainer); break;
                    case 'witness': refreshWitnesses(currentDataTableContainer); break;
                    case 'evidence': refreshEvidence(currentDataTableContainer);
                        break;
                }
            } else {
                // If already visible, hide it
                targetManagementArea.style.display = 'none';
                dynamicTableHeading.textContent = 'Select a category above to view its records.';
                // Hide any form within this area if it was open
                const formInThisArea = targetManagementArea.querySelector('.form-container');
                if (formInThisArea) formInThisArea.style.display = 'none';
            }

            // Scroll to the table display area
            dynamicTableDisplayArea.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    // --- "Cancel" button for forms inside management areas ---
    document.querySelectorAll('.cancel-form-btn').forEach(button => {
        button.addEventListener('click', function() {
            const formContainer = this.closest('.form-container');
            if (formContainer) {
                formContainer.style.display = 'none';
                // Scroll back to the top of the table if 
                formContainer.closest('.data-management-area').scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
    // --- Generic function to display messages ---
    function showMessage(element, message, type) {
        element.textContent = message;
        element.className = `message-area ${type}`;
        element.style.display = 'block';
        setTimeout(() => {
            element.style.display = 'none';
            element.textContent = '';
            element.className = 'message-area';
        }, 5000);
    }
    // Using globalMessageArea for consistency
    function showGlobalMessage(message, type) {
        showMessage(globalMessageArea, message, type);
    }

    // --- Generic function to create a table from data ---
    function createTable(data, containerElement, columns, actions = [], addFormId = null) {
        // Clear previous content but preserve headings if they are added outside this function
        const existingHeadings = containerElement.querySelectorAll('h4');
        containerElement.innerHTML = '';
        existingHeadings.forEach(heading => containerElement.appendChild(heading));


        // Create an "Add New" button for this specific table type if addFormId is provided
        if (addFormId) {
            const addBtnContainer = document.createElement('div');
            addBtnContainer.classList.add('table-actions-top');
            const addBtn = document.createElement('button');
            addBtn.textContent = `+ Add New ${addFormId.replace('add', '').replace('Form', '')}`;
            addBtn.classList.add('btn', 'btn-add-new');
            addBtn.addEventListener('click', () => {
                const formContainer = document.getElementById(addFormId).closest('.form-container');
                if (formContainer) {
                    formContainer.style.display = 'block'; // Show the form container
                    formContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });

                }
            });
            addBtnContainer.appendChild(addBtn);
            containerElement.appendChild(addBtnContainer);
        }

        if (!data || data.length === 0) {
            const noData = document.createElement('p');
            noData.classList.add('no-data-message');
            noData.textContent = 'No data found.';
            containerElement.appendChild(noData);
            return;
        }

        const tableResponsiveDiv = document.createElement('div');
        tableResponsiveDiv.classList.add('table-responsive'); // Add a class for responsive table wrapper

        const table = document.createElement('table');
        const thead = document.createElement('thead');
        const tbody = document.createElement('tbody');

        // Create table header
        const headerRow = document.createElement('tr');
        columns.forEach(col => {
            const th = document.createElement('th');
            th.textContent = col.header;
            headerRow.appendChild(th);
        });
        if (actions.length > 0) {
            const thActions = document.createElement('th');
            thActions.textContent = 'Actions';
            headerRow.appendChild(thActions);
        }
        thead.appendChild(headerRow);
        table.appendChild(thead);
        // Populate table body
        data.forEach(item => {
            const row = document.createElement('tr');
            columns.forEach(col => {
                const td = document.createElement('td');
                if (col.field.toLowerCase().includes('date')) {
                    td.textContent
                        = item[col.field] ? new Date(item[col.field]).toLocaleDateString('en-US') : '';
                } else if (col.field.toLowerCase().includes('time')) {
                    td.textContent = item[col.field] ? item[col.field].substring(0, 5) : '';
                } else {
                    td.textContent = item[col.field];

                }
                row.appendChild(td);
            });

            if (actions.length > 0) {
                const tdActions = document.createElement('td');
                actions.forEach(action => {

                    const button = document.createElement('button');
                    button.textContent = action.label;
                    button.classList.add(action.className);
                    button.dataset.id = item[action.idField];
                    if (action.label === 'Edit') {
                        button.addEventListener('click', () => openEditModal(item, action.type, action.idField));
                    } else if (action.label === 'Delete') {
                        button.addEventListener('click', () => action.onClick(button.dataset.id));
                    }
                    tdActions.appendChild(button);
                });
                tdActions.classList.add('action-buttons-cell'); // Add class for consistent styling
                row.appendChild(tdActions);
            }
            tbody.appendChild(row);
        });
        table.appendChild(tbody);
        tableResponsiveDiv.appendChild(table);
        containerElement.appendChild(tableResponsiveDiv);
    }


    // --- Fetch Data Function ---
    // Updated to handle both GET (for queryParams) and POST (for formData)
    async function fetchData(endpoint, formData = null) {
        console.log('Fetching data from:', endpoint); // Debugging: Log the endpoint
        let options = {};
        let url = endpoint;

        if (formData) {
            // If formData is an object, convert it to URLSearchParams for GET or FormData for POST
            // For search functions, your server.php expects GET, so we'll convert to query params
            if (typeof formData === 'object' && ! (formData instanceof FormData)) {
                const params = new URLSearchParams(formData);
                url = `${endpoint}?${params.toString()}`;
            } else {
                // If it's a FormData object, use POST method
                options = {
                    method: 'POST',
                    body: formData
                };
            }
        }
        
        try {
            const response = await fetch(url, options);
            if (!response.ok) {
                const errorText = await response.text(); // Get raw response text for debugging
                console.error(`HTTP error! Status: ${response.status}`, `Response Text: ${errorText}`);
                if (response.status === 404) {
                    showMessage(globalMessageArea, `Endpoint not found: ${endpoint}. Please check the URL and server-side script.`, 'error');
                } else {
                    showMessage(globalMessageArea, `Server error: ${response.status}. Details: ${errorText.substring(0, 100)}...`, 'error');
                }
                return { success: false, data: [], message: `Server error: ${response.status}` };
            }
            const data = await response.json();
            console.log('Received data:', data); // Debugging: Log the received JSON
            // Ensure data.data exists for data fetching, otherwise return empty array
            return data.data !== undefined ?
                data : { success: true, data: data };
        } catch (error) {
            console.error('Error fetching data from ' + endpoint + ':', error);
            showMessage(globalMessageArea, `Error fetching data from ${endpoint.split('?')[0]}. Please check server.php and database connection.`, 'error');
            return { success: false, data: [], message: `Error fetching data from ${endpoint.split('?')[0]}.` };
        }
    }

    // --- Populate Dropdown Function ---
  async function populateDropdown(selectElementId, endpoint, valueField, textField, allOptionText = null) {
    const selectElement = document.getElementById(selectElementId);
    if (!selectElement) {
        console.error(`Dropdown element with ID "${selectElementId}" not found.`);
        return;
    }
    const responseData = await fetchData(endpoint);
    const data = responseData.data || [];
    
    selectElement.innerHTML = ''; // Clear existing options

    // Add default "Select All" option
    const defaultOption = document.createElement('option');
    defaultOption.value = ''; // This value will be treated as empty, allowing PHP to convert to NULL
    defaultOption.textContent = allOptionText || `Select ${textField.replace('ID', '').replace('Name', '').replace('Type', '')} (All)`;
    selectElement.appendChild(defaultOption);

    if (data && data.length > 0) {
        data.forEach(item => {
            const option = document.createElement('option');
            option.value = item[valueField];
            option.textContent = item[textField];
            selectElement.appendChild(option);
        });
    }
}


    // --- Refresh Data Functions (for each entity) ---
    // These functions now take a container element and the addFormId
    async function refreshClients(container = document.getElementById('clientTableContainer')) {
        const clientsResponse = await fetchData('client_crud.php?action=get_all');
        createTable(clientsResponse.data, container, [
            { header: 'Client ID', field: 'ClientID' },
            { header: 'Name', field: 'Name' },
            { header: 'Email', field: 'Email' },
            { header: 'Address', field: 'Address' },
            { header: 'Phone', field: 'Phone' }
        ], [
            { label: 'Edit', className: 'edit-btn', type: 'client', idField: 'ClientID' },
            { label: 'Delete', className: 'delete-btn', type: 'client', idField: 'ClientID', onClick: deleteClient }
        ], 'addClientForm');
        populateDropdown('clientId', 'client_crud.php?action=get_all', 'ClientID', 'Name');
    }

    async function refreshLawyers(container = document.getElementById('lawyerTableContainer')) {
        const lawyersResponse = await fetchData('lawyer_crud.php?action=get_all');
        createTable(lawyersResponse.data, container, [
            { header: 'Lawyer ID', field: 'LawyerID' },
            { header: 'Name', field: 'Name' },
            { header: 'Firm', field: 'Firm' },
            { header: 'Specialization', field: 'Specialization' },
            { header: 'Email', field: 'Email' },
            { header: 'Phone', field: 'PhoneNumber' }
        ], [
            { label: 'Edit', className: 'edit-btn', type: 'lawyer', idField: 'LawyerID' },
            { label: 'Delete', className: 'delete-btn', type: 'lawyer', idField: 'LawyerID', onClick: deleteLawyer }
        ], 'addLawyerForm');
        populateDropdown('lawyerId', 'lawyer_crud.php?action=get_all', 'LawyerID', 'Name');
    }

    async function refreshCases(container = document.getElementById('caseTableContainer')) {
        const casesResponse = await fetchData('cases_crud.php?action=get_all');
        createTable(casesResponse.data, container, [
            { header: 'Case ID', field: 'CaseID' },
            { header: 'Case Type', field: 'CaseType' },
            { header: 'Status', field: 'Status' },
            { header: 'Filing Date', field: 'FilingDate' },
            { header: 'Client ID', field: 'ClientID' },
            { header: 'Lawyer ID', field: 'LawyerID' }
        ], [
            { label: 'Edit', className: 'edit-btn', type: 'case', idField: 'CaseID' },
            { label: 'Delete', className: 'delete-btn', type: 'case', idField: 'CaseID', onClick: deleteCase }
        ], 'addCaseForm');
        populateDropdown('caseIdHearing', 'cases_crud.php?action=get_all', 'CaseID', 'CaseType');
        populateDropdown('caseIdWitness', 'cases_crud.php?action=get_all', 'CaseID', 'CaseType');
        populateDropdown('caseIdEvidence', 'cases_crud.php?action=get_all', 'CaseID', 'CaseType');
        updateDashboardSummary();
    }

    async function refreshHearings(container = document.getElementById('hearingTableContainer')) {
        const hearingsResponse = await fetchData('hearing_crud.php?action=get_all');
        createTable(hearingsResponse.data, container, [
            { header: 'Hearing ID', field: 'HearingID' },
            { header: 'Case ID', field: 'CaseID' },
            { header: 'Hearing Date', field: 'HearingDate' },
            { header: 'Court Location', field: 'CourtLocation' },
            { header: 'Hearing Time', field: 'HearingTime' }
        ], [
            { label: 'Edit', className: 'edit-btn', type: 'hearing', idField: 'HearingID' },
            { label: 'Delete', className: 'delete-btn', type: 'hearing', idField: 'HearingID', onClick: deleteHearing }
        ], 'addHearingForm');
    }

    async function refreshWitnesses(container = document.getElementById('witnessTableContainer')) {
        const witnessesResponse = await fetchData('witness_crud.php?action=get_all');
        createTable(witnessesResponse.data, container, [
            { header: 'Witness ID', field: 'WitnessID' },
            { header: 'Case ID', field: 'CaseID' },
            { header: 'Name', field: 'Name' },
            { header: 'Contact', field: 'Contact' },
            { header: 'Evidence ID', field: 'EvidenceID' }
        ], [
            { label: 'Edit', className: 'edit-btn', type: 'witness', idField: 'WitnessID' },
            { label: 'Delete', className: 'delete-btn', type: 'witness', idField: 'WitnessID', onClick: deleteWitness }
        ], 'addWitnessForm');
    }

    async function refreshEvidence(container = document.getElementById('evidenceTableContainer')) {
        const evidenceResponse = await fetchData('evidence_crud.php?action=get_all');
        createTable(evidenceResponse.data, container, [
            { header: 'Evidence ID', field: 'EvidenceID' },
            { header: 'Case ID', field: 'CaseID' },
            { header: 'Evidence Type', field: 'EvidenceType' },
            { header: 'Description', field: 'Description' }
        ], [
            { label: 'Edit', className: 'edit-btn', type: 'evidence', idField: 'EvidenceID' },
            { label: 'Delete', className: 'delete-btn', type: 'evidence', idField: 'EvidenceID', onClick: deleteEvidence }
        ], 'addEvidenceForm');
    }


    // --- Initial Load (Dashboard and Search Dropdowns) ---
    updateDashboardSummary();
    populateDropdown('searchCaseType', 'get_distinct_case_types.php', 'CaseType', 'CaseType');
    // NEW: Populate dropdowns for Lawyer Search
    populateDropdown('searchLawyerFirm', 'server.php?action=get_distinct_firms', 'Firm', 'Firm', 'Firm (All)');
    populateDropdown('searchLawyerSpecialization', 'server.php?action=get_distinct_specializations', 'Specialization', 'Specialization', 'Specialization (All)');
    // NEW: Populate dropdown for Client Search
    populateDropdown('searchClientAddress', 'server.php?action=get_distinct_client_addresses', 'Address', 'Address', 'Address (All)');



    // --- Add Operations (Forms are now inside data-management-area) ---
    document.getElementById('addClientForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        formData.append('action', 'add');
        const response = await fetch(`client_crud.php`, { method: 'POST', body: formData });
        const result = await response.json();
        if (result.success) {
            showMessage(globalMessageArea, result.message, 'success');
            e.target.reset();
            refreshClients();
            populateDropdown('clientId', 'client_crud.php?action=get_all', 'ClientID', 'Name');
            populateDropdown('searchClientAddress', 'server.php?action=get_distinct_client_addresses', 'Address', 'Address', 'Address (All)'); // Refresh dropdown
            e.target.closest('.form-container').style.display = 'none'; // Hide form after submission
        } else {
            showMessage(globalMessageArea, result.message, 'error');
        }
    });
    document.getElementById('addLawyerForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        formData.append('action', 'add');
        const response = await fetch(`/lawyer_crud.php`, { method: 'POST', body: formData });
        const result = await response.json();
        if (result.success) {
            showMessage(globalMessageArea, result.message, 'success');
            e.target.reset();
            refreshLawyers();
            populateDropdown('lawyerId', 'lawyer_crud.php?action=get_all', 'LawyerID', 'Name');
            populateDropdown('searchLawyerFirm', 'server.php?action=get_distinct_firms', 'Firm', 'Firm', 'Firm (All)'); // Refresh dropdown
            populateDropdown('searchLawyerSpecialization', 'server.php?action=get_distinct_specializations', 'Specialization', 'Specialization', 'Specialization (All)'); // Refresh dropdown
            e.target.closest('.form-container').style.display = 'none';
        } else {
            showMessage(globalMessageArea, result.message, 'error');
        }
    });
    document.getElementById('addCaseForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        formData.append('action', 'add');
        const response = await fetch(`/cases_crud.php`, { method: 'POST', body: formData });
        const result = await response.json();
        if (result.success) {
            showMessage(globalMessageArea, result.message, 'success');
            e.target.reset();
            refreshCases();
            e.target.closest('.form-container').style.display = 'none';
        } else {
            showMessage(globalMessageArea, result.message, 'error');
        }
    });
    document.getElementById('addHearingForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        formData.append('action', 'add');
        const response = await fetch(`/hearing_crud.php`, { method: 'POST', body: formData });
        const result = await response.json();
        if (result.success) {
            showMessage(globalMessageArea, result.message, 'success');
            e.target.reset();
            refreshHearings();
            e.target.closest('.form-container').style.display = 'none';
        } else {
            showMessage(globalMessageArea, result.message, 'error');
        }
    });
    document.getElementById('addWitnessForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        formData.append('action', 'add');
        const response = await fetch(`/witness_crud.php`, { method: 'POST', body: formData });
        const result = await response.json();
        if (result.success) {
            showMessage(globalMessageArea, result.message, 'success');
            e.target.reset();
            refreshWitnesses();
            e.target.closest('.form-container').style.display = 'none';
        } else {
            showMessage(globalMessageArea, result.message, 'error');
        }
    });
    document.getElementById('addEvidenceForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        formData.append('action', 'add');
        const response = await fetch(`/evidence_crud.php`, { method: 'POST', body: formData });
        const result = await response.json();
        if (result.success) {
            showMessage(globalMessageArea, result.message, 'success');
            e.target.reset();
            refreshEvidence();
            e.target.closest('.form-container').style.display = 'none';
        } else {
            showMessage(globalMessageArea, result.message, 'error');
        }
    });
    // --- Delete Operations ---
    const deleteClient = (id) => deleteRecord(id, 'client_crud.php', refreshClients, 'client');
    const deleteLawyer = (id) => deleteRecord(id, 'lawyer_crud.php', refreshLawyers, 'lawyer');
    const deleteCase = (id) => deleteRecord(id, 'cases_crud.php', refreshCases, 'case');
    const deleteHearing = (id) => deleteRecord(id, 'hearing_crud.php', refreshHearings, 'hearing');
    const deleteWitness = (id) => deleteRecord(id, 'witness_crud.php', refreshWitnesses, 'witness');
    const deleteEvidence = (id) => deleteRecord(id, 'evidence_crud.php', refreshEvidence, 'evidence');

    async function deleteRecord(id, endpoint, refreshFunction, typeName) {
        if (!confirm(`Are you sure you want to delete this ${typeName} record (ID: ${id})?`)) {
            return;
        }
        try {
            const response = await fetch(`/${endpoint}`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, action: 'delete' })
            });
            const result = await response.json();
            if (result.success) {
                showMessage(globalMessageArea, result.message, 'success');
                refreshFunction();
                updateDashboardSummary();
                // NEW: Refresh dropdowns after deletion in case it affects available options
                populateDropdown('searchLawyerFirm', 'server.php?action=get_distinct_firms', 'Firm', 'Firm', 'Firm (All)');
                populateDropdown('searchLawyerSpecialization', 'server.php?action=get_distinct_specializations', 'Specialization', 'Specialization', 'Specialization (All)');
                populateDropdown('searchClientAddress', 'server.php?action=get_distinct_client_addresses', 'Address', 'Address', 'Address (All)');
            } else {
                showMessage(globalMessageArea, result.message, 'error');
            }
        } catch (error) {
            console.error(`Error deleting ${typeName}:`, error);
            showMessage(globalMessageArea, `An error occurred while deleting ${typeName}.`, 'error');
        }
    }


    // --- Edit Modal Logic ---
    const editModal = document.getElementById('editModal');
    const modalCloseBtn = document.querySelector('.modal-close-btn');
    const editForm = document.getElementById('editForm');
    const modalFormFields = document.getElementById('modalFormFields');
    const editMessage = document.getElementById('editMessage');
    let currentEditType = '';
    let currentEditId = null;

    modalCloseBtn.addEventListener('click', () => {
        editModal.style.display = 'none';
        editMessage.style.display = 'none';
    });
    window.addEventListener('click', (event) => {
        if (event.target === editModal) {
            editModal.style.display = 'none';
            editMessage.style.display = 'none';
        }
    });
    async function openEditModal(item, type, idField) {
        editModal.style.display = 'flex';
        modalFormFields.innerHTML = '';
        editMessage.style.display = 'none';

        currentEditType = type;
        currentEditId = item[idField];

        let fields = [];
        let dropdownsToPopulate = [];
        switch (type) {
            case 'client':
                fields = [
                    { name: 'Name', label: 'Client Name', type: 'text' },
                    { name: 'Email', label: 'Client Email', type: 'email' },
                    // In edit modal, keep Address as text input for direct editing
                    { name: 'Address', label: 'Client Address', type: 'text' }, 
                    { name: 'Phone', label: 'Client Phone', type: 'text' }
                ];
                break;
            case 'lawyer':
                fields = [
                    { name: 'Name', label: 'Lawyer Name', type: 'text' },
                    // In edit modal, keep Firm and Specialization as text input for direct editing
                    { name: 'Firm', label: 'Firm', type: 'text' },
                    { name: 'Specialization', label: 'Specialization', type: 'text' },
                    { name: 'Email', label: 'Lawyer Email', type: 'email' },
                    { name: 'PhoneNumber', label: 'Phone', type: 'text' }
                ];
                break;
            case 'case':
                fields = [
                    { name: 'CaseType', label: 'Case Type', type: 'text' },
                    { name: 'Status', label: 'Status', type: 'select', options: ['Pending', 'Ongoing', 'Closed'] },
                    {
                        name: 'FilingDate', label: 'Filing Date', type: 'date' },
                    { name: 'ClientID', label: 'Client', type: 'select', endpoint: 'client_crud.php?action=get_all', valueField: 'ClientID', textField: 'Name' },
                    { name: 'LawyerID', label: 'Lawyer', type: 'select', endpoint: 'lawyer_crud.php?action=get_all', valueField: 'LawyerID', textField: 'Name' }
                ];
                dropdownsToPopulate = ['ClientID', 'LawyerID'];
                break;
            case 'hearing':
                fields = [
                    { name: 'CaseID', label: 'Case', type: 'select', endpoint: 'cases_crud.php?action=get_all', valueField: 'CaseID', textField: 'CaseType' },
                    { name: 'HearingDate', label: 'Hearing Date', type: 'date' },
                    { name: 'CourtLocation', label: 'Court Location', type: 'text' },
                    { name: 'HearingTime', label: 'Hearing Time', type: 'time' }
                ];
                dropdownsToPopulate = ['CaseID'];
                break;
            case 'witness':
                fields = [
                    { name: 'Name', label: 'Witness Name', type: 'text' },
                    { name: 'Contact', label: 'Contact', type: 'text' },
                    { name:
                        'CaseID', label: 'Case', type: 'select', endpoint: 'cases_crud.php?action=get_all', valueField: 'CaseID', textField: 'CaseType' },
                    { name: 'EvidenceID', label: 'Evidence ID (Optional)', type: 'number' }
                ];
                dropdownsToPopulate = ['CaseID'];
                break;
            case 'evidence':
                fields = [
                    { name: 'CaseID', label: 'Case', type: 'select', endpoint: 'cases_crud.php?action=get_all', valueField: 'CaseID', textField: 'CaseType' },
                    { name: 'EvidenceType', label: 'Evidence Type', type: 'text' },
                    { name: 'Description', label: 'Description', type: 'text' }
                ];
                dropdownsToPopulate = ['CaseID'];
                break;
        }

        for (const field of fields) {
            const formGroup = document.createElement('div');
            formGroup.classList.add('form-group');

            const label = document.createElement('label');
            label.setAttribute('for', `edit-${field.name}`);
            label.textContent = field.label + ':';
            formGroup.appendChild(label);

            let inputElement;
            if (field.type === 'select') {
                inputElement = document.createElement('select');
                if (field.options) {
                    field.options.forEach(optionText => {
                        const option = document.createElement('option');
                        option.value = optionText;
                        option.textContent = optionText;
                        inputElement.appendChild(option);
                    });
                }
            } else {
                inputElement = document.createElement('input');
                inputElement.type = field.type;
            }

            inputElement.id = `edit-${field.name}`;
            inputElement.name = field.name;
            inputElement.value = item[field.name] || '';

            if (field.type === 'date' && item[field.name]) {
                inputElement.value = item[field.name].split(' ')[0];
            } else if (field.type === 'time' && item[field.name]) {
                inputElement.value = item[field.name].substring(0, 5);
            }

            formGroup.appendChild(inputElement);
            modalFormFields.appendChild(formGroup);
        }

        for (const fieldName of dropdownsToPopulate) {
            const fieldDef = fields.find(f => f.name === fieldName);
            if (fieldDef && fieldDef.type === 'select') {
                const selectElement = document.getElementById(`edit-${fieldDef.name}`);
                const dataResponse = await fetchData(fieldDef.endpoint);
                const dropdownItems = dataResponse.data || [];

                selectElement.innerHTML = `<option value="">Select ${fieldDef.textField.replace('ID', '').replace('Name', '').replace('Type', '')}</option>`;
                dropdownItems.forEach(dItem => {
                    const option = document.createElement('option');
                    option.value = dItem[fieldDef.valueField];
                    option.textContent = dItem[fieldDef.textField];
                    selectElement.appendChild(option);

                });
                selectElement.value = item[fieldName];
            }
        }
    }

    editForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData();
        formData.append('id', currentEditId);
        formData.append('action', `update`);

        const inputs = modalFormFields.querySelectorAll('input, select');
        inputs.forEach(input => {
            if (input.name) {
                formData.append(input.name, input.value);
            }
        });

       
        try {
    const typeToFileMap = {
        client: 'client_crud.php',
        lawyer: 'lawyer_crud.php',
        case: 'cases_crud.php',
        hearing: 'hearing_crud.php',
        witness: 'witness_crud.php',
        evidence: 'evidence_crud.php'
    };

    const endpoint = typeToFileMap[currentEditType];

    const response = await fetch(endpoint, {
        method: 'POST',
        body: formData
    });

    const result = await response.json();

    if (result.success) {
        showMessage(editMessage, result.message, 'success');
        // Refresh specific table based on currentEditType
        const currentTableContainer = document
            .getElementById(`${currentEditType}ManagementArea`)
            .querySelector('.table-container');

        switch (currentEditType) {
            case 'client': refreshClients(currentTableContainer); break;
            case 'lawyer': refreshLawyers(currentTableContainer); break;
            case 'case': refreshCases(currentTableContainer); break;
            case 'hearing': refreshHearings(currentTableContainer); break;
            case 'witness': refreshWitnesses(currentTableContainer); break;
            case 'evidence': refreshEvidence(currentTableContainer); break;
        }

        updateDashboardSummary();

        // Refresh dropdowns
        populateDropdown('searchLawyerFirm', 'server.php?action=get_distinct_firms', 'Firm', 'Firm', 'Firm (All)');
        populateDropdown('searchLawyerSpecialization', 'server.php?action=get_distinct_specializations', 'Specialization', 'Specialization', 'Specialization (All)');
        populateDropdown('searchClientAddress', 'server.php?action=get_distinct_client_addresses', 'Address', 'Address', 'Address (All)');

        setTimeout(() => editModal.style.display = 'none', 1500);
     } 
     else {
        showMessage(editMessage, result.message, 'error');
     }
     } catch (error) {
      console.error('Error updating record:', error);
      showMessage(editMessage, 'An error occurred while updating the record.', 'error');
     }

    });
    // --- Dashboard Summary & Chart ---
    let caseStatusChartInstance = null;
    async function updateDashboardSummary() {
        const dashboardResponse = await fetchData('server.php?action=get_dashboard_counts');
        const dashboardData = dashboardResponse.data || {};
        const clients = dashboardData.clients || 0;
        const activeCases = dashboardData.activeCases || 0;
        const totalLawyers = dashboardData.totalLawyers || 0;
        const casesWonPercentage = dashboardData.casesWonPercentage !== undefined ? dashboardData.casesWonPercentage : 0;

        document.getElementById('totalClients').textContent = clients;
        document.getElementById('totalLawyers').textContent = totalLawyers;
        document.getElementById('activeCases').textContent = activeCases;
        document.getElementById('casesWon').textContent = `${casesWonPercentage}%`;

        const casesResponse = await fetchData('cases_crud.php?action=get_all');
        const cases = casesResponse.data || [];
        const statusCounts = cases.reduce((acc, caseItem) => {
            acc[caseItem.Status] = (acc[caseItem.Status] || 0) + 1;
            return acc;
        }, {});
        const chartLabels = ['Pending', 'Ongoing', 'Closed'];
        const chartData = chartLabels.map(label => statusCounts[label] || 0);
        const backgroundColors = [
            '#FFC107 ', // warm gold for Pending
            '#00796B ', //  teal for Ongoing
            '#827717   '  //olive green for Closed
        ];
        const hoverBackgroundColors = [
            '#FFA000 ',
            '#004D40 ',
            '#685F0F  '
        ];
        const ctx = document.getElementById('caseStatusChart').getContext('2d');

        if (caseStatusChartInstance) {
            caseStatusChartInstance.destroy();
        }

        caseStatusChartInstance = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: chartLabels,
                datasets: [{
                    data: chartData,
                    backgroundColor: backgroundColors,
                    hoverBackgroundColor: hoverBackgroundColors,
                    borderWidth: 1,
                    borderColor: '#2C2C2C'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                // NEW: Set background color of the chart itself
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            color: '#E0E0E0 ', // Dark text for legend labels
                            font: {
                                size: 14
                            }
                        }
                    },
                    title: {
                        display: false,
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed !== null) {
                                    label += context.parsed + ' cases';
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }

    // --- Quick Reports & Search Forms ---
    document.getElementById('searchCaseForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const caseID = document.getElementById('searchCaseID').value;
        const caseStatus = document.getElementById('searchCaseStatus').value;
        const caseType = document.getElementById('searchCaseType').value;

        const queryParams = new URLSearchParams();
        if (caseID) queryParams.append('case_no', caseID);
        if (caseStatus) queryParams.append('status', caseStatus);
        if (caseType) queryParams.append('case_type', caseType);

        console.log('Search Cases Query Params:', queryParams.toString()); // Debugging

        const dataResponse = await fetchData(`server.php?action=search_cases&${queryParams.toString()}`);
        const data = dataResponse.data || [];
        console.log('Search Cases Data Response:', dataResponse); // Debugging

        document.getElementById('dynamicSearchResultsDisplay').style.display = 'block';
        document.getElementById('dynamicSearchResultsDisplay').querySelector('.dynamic-table-heading').textContent = 'Case Search Results';
        createTable(data, document.getElementById('currentSearchResultsTable'), [
            { header: 'Case ID', field: 'CaseID' },
            { header: 'Case Type', field: 'CaseType' },
            { header: 'Status', field: 'Status' },
            { header: 'Filing Date', field: 'FilingDate' },
            { header: 'Client ID', field: 'ClientID' },
            { header: 'Lawyer ID', field: 'LawyerID' },
            { header: 'Client Name', field: 'ClientName' }, // Added for more context
            { header: 'Lawyer Name', field: 'LawyerName' } // Added for more context
        ]);
        document.getElementById('dynamicSearchResultsDisplay').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    document.getElementById('searchHearingsForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const courtLocation = document.getElementById('searchHearingCourtLocation').value;
        const hearingDate = document.getElementById('searchHearingDate').value;

        const queryParams = new URLSearchParams();
        if (courtLocation) queryParams.append('court_filter', courtLocation);
        if (hearingDate) queryParams.append('hearing_date', hearingDate);

        console.log('Search Hearings Query Params:', queryParams.toString()); // Debugging

        const dataResponse =
            await fetchData(`server.php?action=search_hearings_by_court&${queryParams.toString()}`);
        const data = dataResponse.data || [];
        console.log('Search Hearings Data Response:', dataResponse); // Debugging

        document.getElementById('dynamicSearchResultsDisplay').style.display = 'block';
        document.getElementById('dynamicSearchResultsDisplay').querySelector('.dynamic-table-heading').textContent = 'Hearing Search Results';
        createTable(data, document.getElementById('currentSearchResultsTable'), [
            { header: 'Hearing ID', field: 'HearingID' },
            { header: 'Case ID', field: 'CaseID' },
            { header: 'Hearing Date', field: 'HearingDate' },
            { header: 'Court Location', field: 'CourtLocation' },
            { header: 'Hearing Time', field: 'HearingTime' },
            { header: 'Case Type', field: 'CaseType' }, // Added for more context
            { header: 'Case Status', field: 'Status' }, // Added for more context
            { header: 'Case Name', field: 'CaseName' } // Added for more context
        ]);
        document.getElementById('dynamicSearchResultsDisplay').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });



    // Search Combined (Witnesses & Evidence) Form
    const searchCombinedForm = document.getElementById('searchCombinedForm');
    if (searchCombinedForm) {
        searchCombinedForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            showGlobalMessage('Searching for witnesses and evidence...', 'info');

            const witnessName = document.getElementById('searchWitnessName').value;
            const evidenceType = document.getElementById('searchEvidenceType').value; // Now a dropdown

            const queryParams = new URLSearchParams();
            queryParams.append('action', 'search_witnesses_and_evidence');
            if (witnessName) queryParams.append('witness_name', witnessName);
            if (evidenceType) queryParams.append('evidence_type', evidenceType);

            const dataResponse = await fetchData(`server.php?${queryParams.toString()}`);
            const data = dataResponse.data || []; // Now data is a single array of combined results
            const searchResultsTableContainer = document.getElementById('currentSearchResultsTable');
            searchResultsTableContainer.innerHTML = ''; // Clear previous results

            document.getElementById('dynamicSearchResultsDisplay').style.display = 'block';
            document.getElementById('dynamicSearchResultsDisplay').querySelector('.dynamic-table-heading').textContent = 'Witness & Evidence Search Results';

            if (data.length > 0) {
                createTable(data, searchResultsTableContainer, [
                    { header: 'Case ID', field: 'CaseID' },
                    { header: 'Case Type', field: 'CaseType' },
                    { header: 'Case Status', field: 'CaseStatus' },
                    { header: 'Client Name', field: 'ClientName' },
                    { header: 'Lawyer Name', field: 'LawyerName' },
                    { header: 'Witnesses', field: 'WitnessesNames' },
                    { header: 'Witness Contacts', field: 'WitnessesContacts' },
                    { header: 'Witness Testimonies', field: 'WitnessesTestimonies' }, // Assuming this field exists in your DB
                    { header: 'Evidence Types', field: 'EvidenceTypes' },
                    { header: 'Evidence Descriptions', field: 'EvidenceDescriptions' }
                ]);
                document.getElementById('dynamicSearchResultsDisplay').scrollIntoView({ behavior: 'smooth', block: 'start' });
                showGlobalMessage('Witnesses and evidence found successfully.', 'success');
            } else {
                const noResults = document.createElement('p');
                noResults.textContent = dataResponse.message || 'No witnesses or evidence found matching your criteria.';
                searchResultsTableContainer.appendChild(noResults);
                document.getElementById('dynamicSearchResultsDisplay').scrollIntoView({ behavior: 'smooth', block: 'start' });
                showGlobalMessage(dataResponse.message || 'No witnesses or evidence found.', 'warning');
            }
        });
    }
    // Populate distinct case types for search filter
    async function populateSearchCaseTypes() {
        const dataResponse = await fetchData('get_distinct_case_types.php');
        const data = dataResponse.data || [];
        const selectElement = document.getElementById('searchCaseType');
        if (selectElement) {
            selectElement.innerHTML = '<option value="">Select Case Type (All)</option>';
            data.forEach(item => {
                const option = document.createElement('option');
                option.value = item.CaseType;
                option.textContent = item.CaseType;
                selectElement.appendChild(option);
            });
        }
    }
    populateSearchCaseTypes();

    // Populate distinct evidence types for search filter
    async function populateSearchEvidenceTypes() {
        const dataResponse = await fetchData('server.php?action=get_distinct_evidence_types');
        const data = dataResponse.data || [];
        const selectElement = document.getElementById('searchEvidenceType');
        if (selectElement) {
            selectElement.innerHTML = '<option value="">Select Evidence Type (All)</option>';
            data.forEach(item => {
                const option = document.createElement('option');
                option.value = item.EvidenceType;
                option.textContent = item.EvidenceType;
                selectElement.appendChild(option);
            });
        }
    }
    populateSearchEvidenceTypes(); // Call on initial load

    // --- Handle Lawyer Search Form Submission ---
    const searchLawyerForm = document.getElementById('searchLawyerForm');
    if (searchLawyerForm) {
        searchLawyerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            showGlobalMessage('Searching for lawyers...', 'info');

            const lawyerName = document.getElementById('searchLawyerName').value;
            // Get values from dropdowns
            const lawyerFirm = document.getElementById('searchLawyerFirm').value;
            const lawyerSpecialization = document.getElementById('searchLawyerSpecialization').value;

            const queryParams = new URLSearchParams();
            queryParams.append('action', 'searchLawyers'); // Action for server.php
            if (lawyerName) queryParams.append('lawyer_name', lawyerName);
            if (lawyerFirm) queryParams.append('lawyer_firm', lawyerFirm);
            if (lawyerSpecialization) queryParams.append('lawyer_specialization', lawyerSpecialization);

            const dataResponse = await fetchData(`server.php?${queryParams.toString()}`); // Use GET for search actions
            const searchResultsTableContainer = document.getElementById('currentSearchResultsTable');
            searchResultsTableContainer.innerHTML = ''; // Clear previous results

            if (dataResponse.success && dataResponse.data && dataResponse.data.length
                > 0) {
                const data = dataResponse.data;
                document.getElementById('dynamicSearchResultsDisplay').style.display = 'block';
                document.getElementById('dynamicSearchResultsDisplay').querySelector('.dynamic-table-heading').textContent = 'Lawyer Search Results';
                createTable(data, searchResultsTableContainer, [
                    { header: 'Lawyer ID', field: 'LawyerID' },
                    { header: 'Name', field: 'LawyerName' }, // Changed to LawyerName as per server.php alias
                    { header: 'Firm', field: 'Firm' },
                    { header: 'Specialization', field: 'Specialization' },
                    { header: 'Email', field: 'LawyerEmail' }, // Changed to LawyerEmail as per server.php alias
                    { header: 'Phone Number', field: 'PhoneNumber' },
                    { header: 'Cases Handled', field: 'HandledCaseTypes' }, // New column
                    { header: 'Total Cases', field: 'TotalCasesHandled' }, // New column
                    { header: 'Clients/Cases', field: 'AssociatedClientsAndCases' } // New column
                ]);
                document.getElementById('dynamicSearchResultsDisplay').scrollIntoView({ behavior: 'smooth', block: 'start' });
                showGlobalMessage('Lawyers found successfully.', 'success');
            } else {
                document.getElementById('dynamicSearchResultsDisplay').style.display = 'block';
                document.getElementById('dynamicSearchResultsDisplay').querySelector('.dynamic-table-heading').textContent = 'Lawyer Search Results';
                const noResults = document.createElement('p');
                noResults.textContent = dataResponse.message || 'No lawyers found matching your criteria.';
                searchResultsTableContainer.appendChild(noResults);
                document.getElementById('dynamicSearchResultsDisplay').scrollIntoView({ behavior: 'smooth', block: 'start' });
                showGlobalMessage(dataResponse.message || 'No lawyers found.', 'warning');
            }
        });
    }

    // --- Handle Client Search Form Submission ---
    const searchClientForm = document.getElementById('searchClientForm');
    if (searchClientForm) {
        searchClientForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            showGlobalMessage('Searching for clients...', 'info');

            const clientName = document.getElementById('searchClientName').value.toLowerCase();
            // Get value from dropdown
            const clientAddress = document.getElementById('searchClientAddress').value.toLowerCase();

            const queryParams = new URLSearchParams();
            queryParams.append('action', 'searchClients'); // Action for server.php
            if (clientName) queryParams.append('client_name', clientName);
            if (clientAddress) queryParams.append('client_address', clientAddress);

            const dataResponse = await fetchData(`server.php?${queryParams.toString()}`); // Use GET for search actions

            const searchResultsTableContainer = document.getElementById('currentSearchResultsTable');
            searchResultsTableContainer.innerHTML = ''; // Clear previous results

            if (dataResponse.success && dataResponse.data && dataResponse.data.length > 0) {
                const data = dataResponse.data;
                document.getElementById('dynamicSearchResultsDisplay').style.display = 'block';
                document.getElementById('dynamicSearchResultsDisplay').querySelector('.dynamic-table-heading').textContent = 'Client Search Results';
                createTable(data, searchResultsTableContainer, [
                    { header: 'Client ID', field: 'ClientID' },
                    { header: 'Name', field: 'ClientName' }, // Changed to ClientName as per server.php alias
                    { header: 'Email', field: 'ClientEmail' }, // Changed to ClientEmail as per server.php alias
                    { header: 'Phone', field: 'ClientPhone' }, // Changed to ClientPhone as per server.php alias
                    { header: 'Address', field: 'ClientAddress' },
                    { header: 'Associated Cases', field: 'AssociatedCaseTypes' }, // New column
                    { header: 'Total Cases', field: 'TotalAssociatedCases' }, // New column
                    { header: 'Associated Lawyers/Cases', field: 'AssociatedLawyersAndCases' } // New column
                ]);
                document.getElementById('dynamicSearchResultsDisplay').scrollIntoView({ behavior: 'smooth', block: 'start' });
                showGlobalMessage('Clients found successfully.', 'success');
            } else {
                document.getElementById('dynamicSearchResultsDisplay').style.display = 'block';
                document.getElementById('dynamicSearchResultsDisplay').querySelector('.dynamic-table-heading').textContent = 'Client Search Results';
                const noResults = document.createElement('p');
                noResults.textContent = dataResponse.message || 'No clients found matching your criteria.';
                searchResultsTableContainer.appendChild(noResults);
                document.getElementById('dynamicSearchResultsDisplay').scrollIntoView({ behavior: 'smooth', block: 'start' });
                showGlobalMessage(dataResponse.message || 'No clients found.', 'warning');
            }
        });
    }

    
    document.querySelectorAll('.view-insight-btn').forEach(button => {
    button.addEventListener('click', async (e) => { // Ensure this is an async function
        e.preventDefault();
        const insightType = button.dataset.insightType;
        showGlobalMessage('Fetching insight data...', 'info');

        const resultsContainer = document.getElementById('currentinsightResultsTable');
        const headingElement = document.querySelector('#dynamicinsightResultsDisplay .dynamic-table-heading');
        resultsContainer.innerHTML = '';
        document.getElementById('dynamicinsightResultsDisplay').style.display = 'block';

        // --- IMPORTANT CHANGE HERE ---
        // Changed to directly use insightType as the action in a GET request.
        // This aligns with server.php expecting `?action=top-lawyers` directly.
        const dataResponse = await fetchData(`server.php?action=${insightType}`);
        // --- END IMPORTANT CHANGE ---

        const data = dataResponse.data || [];

        let columns = [];
        let headingText = "Insight Results"; // This will be overwritten by switch cases
        switch (insightType) {
            case 'top-lawyers':
                headingElement.textContent = 'Top Lawyers Overview';
                columns = [
                    { header: 'Lawyer ID', field: 'LawyerID' },
                    { header: 'Name', field: 'LawyerName' },
                    { header: 'Specialization', field: 'Specialization' },
                    { header: 'Firm', field: 'Firm' },
                    { header: 'Closed Cases', field: 'TotalClosedCases' },
                    { header: 'Current Cases', field: 'TotalCurrentCases' },
                    { header: 'Total Cases', field: 'TotalCasesHandled' },
                    { header: 'Court Locations', field: 'CourtLocations' }
                ];
                break;

            case 'unassigned-clients':
                headingElement.textContent = 'Clients Without Lawyers';
                columns = [
                    { header: 'Client ID', field: 'ClientID' },
                    { header: 'Client Name', field: 'ClientName' },
                    { header: 'Email', field: 'Email' },
                    { header: 'Phone', field: 'Phone' },
                    { header: 'Address', field: 'Address' }
                ];
                break;

            case 'recent-hearings':
                headingElement.textContent = 'Recent Hearing Details';
                columns = [
                    { header: 'Hearing ID', field: 'HearingID' },
                    { header: 'Case ID', field: 'CaseID' },
                    { header: 'Hearing Date', field: 'HearingDate' },
                    { header: 'Court Location', field: 'CourtLocation' },
                    { header: 'Lawyer Name', field: 'LawyerName' },
                    { header: 'Firm', field: 'Firm' }
                ];
                break;

            case 'all-records-view':
                headingElement.textContent = 'Comprehensive Data View';
                columns = [
                    { header: 'Client Name', field: 'ClientName' },
                    { header: 'Case Type', field: 'CaseType' },
                    { header: 'Status', field: 'Status' },
                    { header: 'Lawyer Name', field: 'LawyerName' },
                    { header: 'Lawyer Firm', field: 'Firm' },
                    { header: 'Hearing Date', field: 'HearingDate' },
                    { header: 'Court Location', field: 'CourtLocation' },
                    { header: 'Hearing Date', field: 'HearingDate' },
                    { header: 'Hearing Time', field: 'HearingTime' },
                    { header: 'Witness', field: 'WitnessName' },
                    { header: 'Evidence Types', field: 'EvidenceDescription' }
                ];
                break;

            case 'crime-increase-by-city':
                headingElement.textContent = 'Crime Trend by City & Type';
                columns = [
                    { header: 'City', field: 'City' },
                    { header: 'Most Cases', field: 'MostFrequentCaseType' },
                    { header: 'Case Count', field: 'CaseCount' }
                ];
                break;

            default:
                headingElement.textContent = 'Unknown Insight';
                resultsContainer.innerHTML = '<p class="no-results">Invalid insight type selected.</p>';
                showGlobalMessage('Unknown insight type.', 'warning');
                return;
        }

        if (dataResponse.success && data.length > 0) {
            createTable(data, resultsContainer, columns);
            showGlobalMessage('Insight data loaded successfully.', 'success');
        } else {
            resultsContainer.innerHTML = `<p class="no-results">${dataResponse.message || 'No data found for this insight.'}</p>`;
            showGlobalMessage(dataResponse.message || 'No data found.', 'warning');
        }

        document.getElementById('dynamicinsightResultsDisplay').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
});
    
});