// main.js (Electron)
const { app, BrowserWindow, ipcMain } = require('electron');
const { systemPreferences } = require('electron');

let win;

app.on('ready', () => {
  win = new BrowserWindow({
    width: 800,
    height: 600,
    webPreferences: {
      nodeIntegration: true,
      contextIsolation: false
    }
  });

  win.loadFile('index.php');
});

// Listen for fingerprint request from renderer
ipcMain.handle('check-biometric', async () => {
  try {
    // Ask for Touch ID authentication
    const success = await systemPreferences.promptTouchID('Log in to Engagement Tracker');
    return { success };
  } catch (err) {
    return { success: false, error: err.message };
  }
});
