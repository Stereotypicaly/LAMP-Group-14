const contactsBody = document.querySelector('.data-table tbody');
const contactsMessage = document.getElementById('contacts-message');
const signOutButton = document.getElementById('sign-out-button');

function showTableMessage(message) {
  contactsBody.replaceChildren();

  const row = document.createElement('tr');
  const cell = document.createElement('td');
  cell.colSpan = 6;
  cell.textContent = message;
  row.append(cell);
  contactsBody.append(row);
}

function formatDate(value) {
  return value ? value.replace('T', ' ') : '';
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
      contact.Phone,
      formatDate(contact.DateCreated),
      formatDate(contact.DateUpdated)
    ];

    values.forEach((value) => {
      const cell = document.createElement('td');
      cell.textContent = value || '';
      row.append(cell);
    });

    contactsBody.append(row);
  });
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
