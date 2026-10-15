(() => {
  document.querySelectorAll('input[type="password"]:not([name="confirmation"])').forEach((input, index) => {
    if (input.closest('.password-field')) return;
    if (!input.id) input.id = `password-field-${index}`;
    const wrapper = document.createElement('div');
    wrapper.className = 'password-field';
    input.before(wrapper);
    wrapper.append(input);
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'password-visibility';
    button.setAttribute('aria-controls', input.id);
    const fieldName = input.labels?.[0]?.textContent.trim() || 'contraseña';
    const update = (visible) => {
      input.type = visible ? 'text' : 'password';
      const label = `${visible ? 'Ocultar' : 'Mostrar'}: ${fieldName}`;
      button.setAttribute('aria-label', label);
      button.setAttribute('aria-pressed', String(visible));
      button.title = label;
      button.innerHTML = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>' + (visible ? '<path d="m3 3 18 18"/>' : '') + '</svg>';
    };
    button.addEventListener('click', () => update(input.type === 'password'));
    input.form?.addEventListener('reset', () => update(false));
    window.addEventListener('pageshow', () => update(false));
    update(false);
    wrapper.append(button);
  });
})();
