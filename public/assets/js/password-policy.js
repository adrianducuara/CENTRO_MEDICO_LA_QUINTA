(() => {
  'use strict';
  const configNode = document.querySelector('[data-password-policy-config]');
  if (!configNode) return;
  const config = JSON.parse(configNode.textContent);
  const form = configNode.closest('form');
  const password = form.elements.password;
  const confirmation = form.elements.confirmation;
  const feedback = form.querySelector('#password-feedback');
  const matchFeedback = form.querySelector('#confirmation-feedback');
  const escape = (text) => text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  const same = (value, term) => new RegExp(`^${escape(term)}$`, 'iu').test(value);
  const validate = () => {
    const value = password.value;
    const length = Array.from(value).length;
    let error = '';
    if (length < 8 || length > 16) error = 'Usa entre 8 y 16 caracteres.';
    else if (config.blocklist.some((term) => same(value, term))) error = 'Esta contraseña es demasiado común. Elige una frase menos predecible.';
    else if (/(.)\1{5,}/us.test(value) || /^(.{1,4})\1{2,}$/uis.test(value)) error = 'Evita caracteres o grupos cortos repetidos, como aaaaaa o abcabcabc.';
    else {
      for (const sequence of ['0123456789', 'abcdefghijklmnopqrstuvwxyz', 'qwertyuiop', 'asdfghjkl', 'zxcvbnm']) {
        for (const direction of [sequence, Array.from(sequence).reverse().join('')]) {
          for (let i = 0; i <= direction.length - 4; i++) {
            if (value.toLowerCase().includes(direction.slice(i, i + 4))) error = 'Evita secuencias predecibles, como 1234, abcd o qwerty.';
          }
        }
      }
      if (!error && config.terms.some((term) => new RegExp(escape(term), 'iu').test(value))) error = 'No incluyas tu usuario, la parte anterior a @ de tu correo ni tu nombre.';
    }
    const mismatch = value !== confirmation.value ? 'La nueva contraseña y la confirmación no coinciden.' : '';
    password.setCustomValidity(error);
    confirmation.setCustomValidity(mismatch);
    password.setAttribute('aria-invalid', String(Boolean(value && error)));
    confirmation.setAttribute('aria-invalid', String(Boolean(confirmation.value && mismatch)));
    feedback.textContent = value ? (error || `${length} caracteres. Cumple la política.`) : '';
    const matchState = confirmation.value ? (mismatch ? 'mismatch' : 'match') : '';
    confirmation.setAttribute('data-password-match', matchState);
    matchFeedback.className = matchState ? `password-match-feedback password-match-feedback--${matchState}` : '';
    matchFeedback.textContent = confirmation.value ? (mismatch ? `✕ ${mismatch}` : '✓ Las contraseñas coinciden.') : '';
  };
  form.addEventListener('input', validate);
  form.addEventListener('change', validate);
  form.addEventListener('submit', (event) => {
    validate();
    if (!form.reportValidity()) event.preventDefault();
  });
  validate();
})();
