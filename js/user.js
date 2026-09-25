const contactsBody = document.querySelector('.data-table tbody');
const contactsMessage = document.getElementById('contacts-message');
const signOutButton = document.getElementById('sign-out-button');
const contactSearchForm = document.getElementById('contact-search-form');
const contactSearchInput = document.getElementById('contact-search');
const clearSearchButton = document.getElementById('clear-search-button');
const editContactDialog = document.getElementById('edit-contact-dialog');
const editContactForm = document.getElementById('edit-contact-form');
const editContactMessage = document.getElementById('edit-contact-message');
const cancelEditContactButton = document.getElementById('cancel-edit-contact');
const saveEditContactButton = document.getElementById('save-edit-contact');
let contactBeingEdited = null;

function showTableMessage(message) {
  contactsBody.replaceChildren();

  const row = document.createElement('tr');
  const cell = document.createElement('td');
  cell.colSpan = 7;
  cell.textContent = message;
  row.append(cell);
  contactsBody.append(row);
}

function formatDate(value) {
  return value ? value.replace('T', ' ') : '';
}

function formatPhone(value) {
  let digits = String(value || '').replace(/\D/g, '');
  if (digits.length === 11 && digits.startsWith('1')) digits = digits.slice(1);
  return digits.length === 10
    ? `(${digits.slice(0, 3)}) ${digits.slice(3, 6)}-${digits.slice(6)}`
    : value || '';
}

function renderContacts(contacts) {
  contactsBody.replaceChildren();

  if (contacts.length === 0) {
    showTableMessage('No contacts found.');
    return;
  }

  contacts.forEach((contact) => {
    const row = document.createElement('tr');
    const values = [
      contact.FirstName,
      contact.LastName,
      contact.EmailAddress,
      formatPhone(contact.Phone),
      formatDate(contact.DateCreated),
      formatDate(contact.DateUpdated)
    ];

    values.forEach((value) => {
      const cell = document.createElement('td');
      cell.textContent = value || '';
      row.append(cell);
    });

    const actions = document.createElement('td');
    actions.className = 'table-actions contact-actions';
    const editButton = document.createElement('button');
    editButton.type = 'button';
    editButton.className = 'button button--small button--edit';
    editButton.textContent = '\u270E';
    editButton.setAttribute('aria-label', `Edit ${contact.FirstName || ''} ${contact.LastName || ''}`.trim());
    editButton.title = 'Edit contact';
    editButton.addEventListener('click', () => openEditDialog(contact));

    const deleteButton = document.createElement('button');
    deleteButton.type = 'button';
    deleteButton.className = 'button button--small button--danger';
    deleteButton.textContent = 'x';
    deleteButton.setAttribute('aria-label', `Remove ${contact.FirstName || ''} ${contact.LastName || ''}`.trim());
    deleteButton.title = 'Remove contact';
    deleteButton.addEventListener('click', () => deleteContact(contact, row, deleteButton));
    actions.append(editButton, deleteButton);
    row.append(actions);

    contactsBody.append(row);
  });
}

function openEditDialog(contact) {
  contactBeingEdited = contact;
  editContactForm.elements.firstName.value = contact.FirstName || '';
  editContactForm.elements.lastName.value = contact.LastName || '';
  editContactForm.elements.emailAddress.value = contact.EmailAddress || '';
  editContactForm.elements.phone.value = contact.Phone || '';
  editContactMessage.textContent = '';
  editContactMessage.className = 'form-message';
  editContactDialog.showModal();
  editContactForm.elements.firstName.focus();
}

function closeEditDialog() {
  editContactDialog.close();
  contactBeingEdited = null;
}

editContactDialog.addEventListener('close', () => {
  contactBeingEdited = null;
});

editContactForm.addEventListener('submit', async (event) => {
  event.preventDefault();
  if (!contactBeingEdited || !editContactForm.reportValidity()) return;

  const formData = new FormData(editContactForm);
  saveEditContactButton.disabled = true;
  saveEditContactButton.textContent = 'Saving...';
  editContactMessage.textContent = '';

  try {
    const response = await fetch('api/editContact.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
      body: JSON.stringify({
        contactId: contactBeingEdited.ID,
        firstName: formData.get('firstName'),
        lastName: formData.get('lastName'),
        emailAddress: formData.get('emailAddress'),
        phone: formData.get('phone')
      })
    });
    const result = await response.json();

    if (response.status === 401 || response.status === 403) {
      window.location.assign('index.html');
      return;
    }
    if (!response.ok) throw new Error(result.error || 'Unable to update contact.');

    closeEditDialog();
    const searchTerm = contactSearchInput.value.trim();
    if (searchTerm) await searchContacts(searchTerm);
    else await loadContacts();
    setMessage(result.message || 'Contact updated successfully.');
  } catch (error) {
    editContactMessage.textContent = error.message || 'Unable to update contact.';
    editContactMessage.className = 'form-message form-message--error';
  } finally {
    saveEditContactButton.disabled = false;
    saveEditContactButton.textContent = 'Save changes';
  }
});

cancelEditContactButton.addEventListener('click', closeEditDialog);

function setMessage(message, isError = false) {
  contactsMessage.textContent = message;
  contactsMessage.className = `form-message${isError ? ' form-message--error' : ' form-message--success'}`;
}

async function deleteContact(contact, row, button) {
  const contactName = `${contact.FirstName || ''} ${contact.LastName || ''}`.trim() || 'this contact';
  if (!window.confirm(`Remove ${contactName}?`)) return;

  button.disabled = true;
  button.textContent = '...';

  try {
    const response = await fetch('api/deleteContact.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ contactId: contact.ID })
    });
    const result = await response.json();

    if (response.status === 401 || response.status === 403) {
      window.location.assign('index.html');
      return;
    }
    if (!response.ok) throw new Error(result.error || 'Unable to remove contact.');

    row.remove();
    if (contactsBody.children.length === 0) showTableMessage('No contacts found.');
    setMessage(result.message);
  } catch (error) {
    button.disabled = false;
    button.textContent = 'x';
    setMessage(error.message || 'Unable to remove contact.', true);
  }
}

async function loadContacts() {
  try {
    const response = await fetch('api/contacts.php', {
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json'
      }
    });

    const result = await response.json();

    if (response.status === 401 || response.status === 403) {
      window.location.assign('index.html');
      return;
    }

    if (!response.ok) {
      throw new Error(result.error || 'Unable to load contacts.');
    }

    renderContacts(result.contacts);
  } catch (error) {
    showTableMessage('Unable to load contacts.');
    contactsMessage.textContent = error.message || 'Unable to load contacts.';
    contactsMessage.classList.add('form-message--error');
  }
}

async function searchContacts(searchTerm) {
  const submitButton = contactSearchForm.querySelector('button[type="submit"]');
  submitButton.disabled = true;
  submitButton.textContent = 'Searching...';

  try {
    const response = await fetch('api/searchContact.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ searchTerm })
    });
    const result = await response.json();

    if (response.status === 401 || response.status === 403) {
      window.location.assign('index.html');
      return;
    }
    if (!response.ok) throw new Error(result.error || 'Unable to search contacts.');

    renderContacts(result.contacts);
    setMessage(result.contacts.length ? '' : 'No matching contacts found.');
  } catch (error) {
    showTableMessage('Unable to search contacts.');
    setMessage(error.message || 'Unable to search contacts.', true);
  } finally {
    submitButton.disabled = false;
    submitButton.textContent = 'Search';
  }
}

contactSearchForm.addEventListener('submit', async (event) => {
  event.preventDefault();
  const searchTerm = contactSearchInput.value.trim();

  contactsMessage.textContent = '';
  contactsMessage.className = 'form-message';
  if (!searchTerm) {
    await loadContacts();
    return;
  }

  await searchContacts(searchTerm);
});

clearSearchButton.addEventListener('click', () => {
  contactSearchInput.value = '';
  contactsMessage.textContent = '';
  contactsMessage.className = 'form-message';
  loadContacts();
  contactSearchInput.focus();
});

loadContacts();

signOutButton.addEventListener('click', async () => {
  signOutButton.disabled = true;
  signOutButton.textContent = 'Signing out...';

  try {
    await fetch('api/logout.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json'
      }
    });
  } finally {
    window.location.replace('index.html');
  }
});
