// Run with Node.js. Exercises the actual browser script with a minimal form DOM.
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const root = path.resolve(__dirname, '..');
const config = {blocklist: JSON.parse(fs.readFileSync(path.join(root, 'config/password-blocklist.json'), 'utf8')), terms: ['anderson', 'José']};
const input = () => ({value: '', validity: '', setCustomValidity(value) { this.validity = value; }, setAttribute() {}});
const password = input(), confirmation = input();
const feedback = {textContent: ''}, match = {textContent: ''}, listeners = {};
const form = {elements: {password, confirmation}, querySelector: (selector) => selector === '#password-feedback' ? feedback : match, addEventListener: (event, callback) => listeners[event] = callback, reportValidity: () => !password.validity && !confirmation.validity};
const node = {textContent: JSON.stringify(config), closest: () => form};
vm.runInNewContext(fs.readFileSync(path.join(root, 'public/assets/js/password-policy.js'), 'utf8'), {document: {querySelector: () => node}});
let checks = 0;
function check(condition) { if (!condition) throw new Error(`Failed live policy check ${checks + 1}`); checks++; }
function enter(value, repeat = value) { password.value = value; confirmation.value = repeat; listeners.input(); }
for (const value of ['Luna mar', '月🌙山🌲海🍀雲🌻風🌊雨☀', '月🌙山🌲海🍀雲🌻'.repeat(2)]) { enter(value); check(!password.validity && !confirmation.validity); }
for (const value of ['Luna ma', 'PASSWORD123!', 'aaaaaa montes', 'abcabcabcabc', 'abcd1234 mar sol', 'ANDERSON luna', 'JOSÉ montaña', '月🌙山🌲海🍀雲🌻'.repeat(2) + 'a']) { enter(value); check(Boolean(password.validity)); }
enter('viento luna pino', 'viento luna Pino'); check(Boolean(confirmation.validity));
enter('viento luna pino'); check(match.textContent === '✓ Las contraseñas coinciden.');
enter('corta'); let prevented = false; listeners.submit({preventDefault() { prevented = true; }}); check(prevented);
console.log(`PASS: ${checks} live password feedback checks.`);
