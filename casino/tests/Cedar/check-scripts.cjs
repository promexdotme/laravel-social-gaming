const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.resolve(__dirname, '../../..');
let checked = 0;
for (const name of ['CedarDice', 'CedarWheel', 'CedarPlinko', 'CedarMines', 'CedarCrash', 'RoyalSteps']) {
  const html = fs.readFileSync(path.join(root, 'output/playwright/cedar', name + '.html'), 'utf8');
  for (const match of html.matchAll(/<script\b([^>]*)>([\s\S]*?)<\/script>/gi)) {
    if (/\bsrc=/.test(match[1]) || /application\/ld\+json/.test(match[1])) continue;
    new vm.Script(match[2], {filename: name + '.inline.js'});
    checked++;
  }
}
new vm.Script(fs.readFileSync(path.join(root, 'js/cedar-client.js'), 'utf8'), {filename: 'cedar-client.js'});
console.log(`PASS: ${checked} rendered inline scripts and Cedar transport parse successfully.`);
