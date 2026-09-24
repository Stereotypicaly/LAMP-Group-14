const createContactForm = document.getElementById('create-contact-form');
const createContactMessage = document.getElementById('create-contact-message');

createContactForm.addEventListener('submit', async (event) => {
  event.preventDefault();

  if (!createContactForm.reportValidity()) {
    return;
  }

  const submitButton = createContactForm.querySelector('button[type="submit"]');
  const formData = new FormData(createContactForm);

  createContactMessage.textContent = '';
  createContactMessage.className = 'form-message';
  submitButton.disabled = true;
  submitButton.textContent = 'Saving contact...';

  try {
    const response = await fetch('api/contacts.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
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

    if (!response.ok) {
      throw new Error(result.error || 'Unable to save contact.');
    }

    window.location.assign('user.html');
  } catch (error) {
    createContactMessage.textContent = error.message || 'Unable to save contact.';
    createContactMessage.classList.add('form-message--error');
  } finally {
    submitButton.disabled = false;
    submitButton.textContent = 'Save contact';
  }
});
