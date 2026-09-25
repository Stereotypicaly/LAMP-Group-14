const createContactForm = document.getElementById('create-contact-form');
const createContactMessage = document.getElementById('create-contact-message');
const phoneInput = document.getElementById('phone');

function normalizePhoneNumber(value) {
  let digits = value.replace(/\D/g, '');
  if (digits.length === 11 && digits.startsWith('1')) digits = digits.slice(1);
  return digits;
}

function validatePhoneNumber() {
  const value = phoneInput.value.trim();
  const isAllowedFormat = /^\+?[0-9\s().-]+$/.test(value);
  const normalizedPhone = normalizePhoneNumber(value);
  const isValid = isAllowedFormat && normalizedPhone.length === 10;

  phoneInput.setCustomValidity(isValid ? '' : 'Enter a valid 10-digit phone number.');
  return isValid ? normalizedPhone : null;
}

phoneInput.addEventListener('input', () => phoneInput.setCustomValidity(''));
phoneInput.addEventListener('blur', () => {
  const normalizedPhone = validatePhoneNumber();
  if (normalizedPhone) phoneInput.value = normalizedPhone;
});

createContactForm.addEventListener('submit', async (event) => {
  event.preventDefault();

  const normalizedPhone = validatePhoneNumber();
  if (!createContactForm.reportValidity()) {
    return;
  }

  phoneInput.value = normalizedPhone;

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
        phone: normalizedPhone
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
