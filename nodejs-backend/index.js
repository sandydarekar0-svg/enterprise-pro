// nodejs-backend/index.js

const express = require('express');
const { Client, LocalAuth, MessageMedia } = require('whatsapp-web.js');
const QRCode = require('qrcode');
const fs = require('fs');
const path = require('path');
const cors = require('cors');

const app = express();
const PORT = process.env.PORT || 3001;

app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ limit: '50mb', extended: true }));

const clients = new Map();
const qrCodes = new Map();

// Create WhatsApp client
function createWhatsAppClient(userId) {
    if (clients.has(userId)) {
        return clients.get(userId);
    }

    const client = new Client({
        authStrategy: new LocalAuth({ clientId: `user_${userId}` }),
        puppeteer: {
            headless: true,
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-gpu',
                '--single-process'
            ]
        }
    });

    client.on('qr', (qr) => {
        console.log(`[${userId}] QR Code generated`);
        qrCodes.set(userId, qr);
        
        QRCode.toDataURL(qr, (err, url) => {
            if (!err) {
                qrCodes.set(`${userId}_url`, url);
            }
        });
    });

    client.on('ready', () => {
        console.log(`[${userId}] WhatsApp ready`);
        qrCodes.delete(userId);
        qrCodes.set(`${userId}_status`, 'ready');
    });

    client.on('message', async (message) => {
        console.log(`[${userId}] Message:`, message.body);
    });

    client.on('disconnected', (reason) => {
        console.log(`[${userId}] Disconnected:`, reason);
        clients.delete(userId);
        qrCodes.set(`${userId}_status`, 'disconnected');
    });

    client.on('error', (error) => {
        console.error(`[${userId}] Error:`, error);
        qrCodes.set(`${userId}_error`, error.message);
    });

    client.initialize();
    clients.set(userId, client);

    return client;
}

// Wait for client ready
async function waitForClientReady(userId, timeout = 30000) {
    const client = clients.get(userId);
    if (!client) throw new Error('Client not found');

    const startTime = Date.now();
    while (Date.now() - startTime < timeout) {
        if (qrCodes.get(`${userId}_status`) === 'ready') {
            return true;
        }
        await new Promise(resolve => setTimeout(resolve, 100));
    }

    throw new Error('Initialization timeout');
}

// API Routes
app.post('/api/whatsapp/login', async (req, res) => {
    try {
        const { userId } = req.body;
        if (!userId) {
            return res.json({ success: false, message: 'User ID required' });
        }

        const client = createWhatsAppClient(userId);
        await new Promise(resolve => setTimeout(resolve, 2000));

        const qrUrl = qrCodes.get(`${userId}_url`);
        const status = qrCodes.get(`${userId}_status`);

        if (!qrUrl && status !== 'ready') {
            return res.json({
                success: false,
                message: 'QR code not generated yet',
                retry: true
            });
        }

        if (status === 'ready') {
            return res.json({
                success: true,
                message: 'Already logged in',
                status: 'ready'
            });
        }

        res.json({
            success: true,
            message: 'Scan QR code',
            qrCode: qrUrl,
            status: 'waiting'
        });
    } catch (error) {
        console.error('Login error:', error);
        res.json({ success: false, message: error.message });
    }
});

app.get('/api/whatsapp/status/:userId', async (req, res) => {
    try {
        const { userId } = req.params;
        
        const status = qrCodes.get(`${userId}_status`);
        const qrUrl = qrCodes.get(`${userId}_url`);
        const error = qrCodes.get(`${userId}_error`);

        if (status === 'ready') {
            const client = clients.get(userId);
            const info = await client.getWWebVersion();

            return res.json({
                success: true,
                status: 'ready',
                message: 'Connected',
                version: info
            });
        }

        res.json({
            success: true,
            status: status || 'initializing',
            message: status === 'ready' ? 'Connected' : 'Waiting',
            qrCode: qrUrl || null,
            error: error || null
        });
    } catch (error) {
        res.json({ success: false, message: error.message });
    }
});

app.post('/api/whatsapp/send-message', async (req, res) => {
    try {
        const { userId, phone, message } = req.body;

        if (!userId || !phone || !message) {
            return res.json({ 
                success: false, 
                message: 'Missing required fields' 
            });
        }

        const client = clients.get(userId);
        if (!client) {
            return res.json({ success: false, message: 'Client not found' });
        }

        await waitForClientReady(userId, 5000);

        const chatId = phone.includes('@') ? phone : `${phone}@c.us`;
        const response = await client.sendMessage(chatId, message);

        res.json({
            success: true,
            message: 'Message sent',
            messageId: response.id._serialized,
            timestamp: response.timestamp
        });
    } catch (error) {
        console.error('Send error:', error);
        res.json({ success: false, message: error.message });
    }
});

app.get('/api/whatsapp/contacts/:userId', async (req, res) => {
    try {
        const { userId } = req.params;

        const client = clients.get(userId);
        if (!client) {
            return res.json({ success: false, message: 'Client not found' });
        }

        await waitForClientReady(userId, 5000);

        const contacts = await client.getContacts();

        const contactList = contacts.map(c => ({
            id: c.id._serialized,
            name: c.name || 'Unknown',
            phone: c.number || c.id.user,
            isGroup: c.isGroup
        }));

        res.json({
            success: true,
            contacts: contactList,
            count: contactList.length
        });
    } catch (error) {
        res.json({ success: false, message: error.message });
    }
});

app.post('/api/whatsapp/logout/:userId', async (req, res) => {
    try {
        const { userId } = req.params;

        const client = clients.get(userId);
        if (client) {
            await client.logout();
            clients.delete(userId);
        }

        const sessionPath = path.join('.wwebjs_auth', `user_${userId}`);
        if (fs.existsSync(sessionPath)) {
            fs.rmSync(sessionPath, { recursive: true });
        }

        res.json({
            success: true,
            message: 'Logged out'
        });
    } catch (error) {
        res.json({ success: false, message: error.message });
    }
});

app.get('/health', (req, res) => {
    res.json({ status: 'OK', timestamp: new Date() });
});

app.listen(PORT, () => {
    console.log(`WhatsApp Backend running on port ${PORT}`);
});
