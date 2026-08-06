/**
 * Po'Boy Server Side Analytics Automated cPanel Packager Script
 */
const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

console.log("📦 Building Po'Boy Packages...");

const projectRoot = __dirname;
const stagingFolder = path.join(projectRoot, 'poboy_staging');
const targetSubfolder = path.join(stagingFolder, 'poboy');

if (fs.existsSync(stagingFolder)) {
    fs.rmSync(stagingFolder, { recursive: true, force: true });
}

fs.mkdirSync(targetSubfolder, { recursive: true });

// 1. Write Strict Root .htaccess
const rootHtaccess = `# Po'Boy's Data Layer Strict Security Policy
Options -Indexes

<FilesMatch "\\.(sqlite|jsonl|bak|db|gitkeep)$">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
    <IfModule !mod_authz_core.c>
        Order deny,allow
        Deny from all
    </IfModule>
</FilesMatch>

<Files "config.php">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
    <IfModule !mod_authz_core.c>
        Order deny,allow
        Deny from all
    </IfModule>
</Files>
`;
fs.writeFileSync(path.join(targetSubfolder, '.htaccess'), rootHtaccess);

const filesToInclude = [
    'poboy.js',
    'log.php',
    'config.php',
    'dashboard.php',
    'GTM_POBOY_CONTAINER.json'
];

filesToInclude.forEach(file => {
    const srcPath = path.join(projectRoot, file);
    const destPath = path.join(targetSubfolder, file);
    if (fs.existsSync(srcPath)) {
        let content = fs.readFileSync(srcPath, 'utf8');
        fs.writeFileSync(destPath, content);
        console.log(`  ✓ Added ${file}`);
    }
});

const logsFolder = path.join(targetSubfolder, 'logs');
if (!fs.existsSync(logsFolder)) {
    fs.mkdirSync(logsFolder, { recursive: true });
}
const logsHtaccess = `<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
    Order deny,allow
    Deny from all
</IfModule>
`;
fs.writeFileSync(path.join(logsFolder, '.htaccess'), logsHtaccess);
fs.writeFileSync(path.join(logsFolder, '.gitkeep'), '');

const poboyZipPath = path.join(projectRoot, 'poboy-cpanel-turnkey.zip');

if (fs.existsSync(poboyZipPath)) fs.unlinkSync(poboyZipPath);

try {
    const psCmdPoboy = `powershell -Command "Compress-Archive -Path '${targetSubfolder}' -DestinationPath '${poboyZipPath}' -Force"`;
    execSync(psCmdPoboy, { stdio: 'inherit' });

    console.log("\n✅ SUCCESS! Turnkey Package created successfully:");
    console.log(`📁 Zip File: ${poboyZipPath} (${(fs.statSync(poboyZipPath).size / 1024).toFixed(2)} KB)`);
} catch (err) {
    console.error("❌ Failed to create zip archive via PowerShell:", err.message);
}
