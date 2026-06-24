const WebSocket = require('ws');
const http = require('http');
const url = require('url');

const PORT = 8085;

// Rooms Map: pin_code -> Set of WebSocket clients
const rooms = new Map();

// Create HTTP server
const server = http.createServer((req, res) => {
    // Enable CORS for localhost requests
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

    if (req.method === 'OPTIONS') {
        res.writeHead(200);
        res.end();
        return;
    }

    const parsedUrl = url.parse(req.url, true);

    if (parsedUrl.pathname === '/broadcast' && req.method === 'POST') {
        let body = '';
        req.on('data', chunk => {
            body += chunk.toString();
        });
        req.on('end', () => {
            try {
                const payload = JSON.parse(body);
                const { pin_code, event, data } = payload;

                if (!pin_code) {
                    res.writeHead(400, { 'Content-Type': 'application/json' });
                    res.end(JSON.stringify({ error: 'Missing pin_code' }));
                    return;
                }

                // Broadcast to the specific room
                const clients = rooms.get(String(pin_code));
                if (clients) {
                    const message = JSON.stringify({ event, data });
                    let count = 0;
                    clients.forEach(client => {
                        if (client.readyState === WebSocket.OPEN) {
                            client.send(message);
                            count++;
                        }
                    });
                    res.writeHead(200, { 'Content-Type': 'application/json' });
                    res.end(JSON.stringify({ success: true, broadcasted_to: count }));
                } else {
                    res.writeHead(200, { 'Content-Type': 'application/json' });
                    res.end(JSON.stringify({ success: true, broadcasted_to: 0, note: 'No active clients in room' }));
                }
            } catch (err) {
                res.writeHead(400, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ error: 'Invalid JSON body: ' + err.message }));
            }
        });
    } else if (parsedUrl.pathname === '/status' && req.method === 'GET') {
        // Status endpoint to check active rooms and connections
        const status = {};
        rooms.forEach((clients, pin) => {
            status[pin] = clients.size;
        });
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ active_rooms: status, uptime: process.uptime() }));
    } else {
        res.writeHead(404);
        res.end('Not Found');
    }
});

// Create WebSocket server attached to HTTP server
const wss = new WebSocket.Server({ noServer: true });

server.on('upgrade', (request, socket, head) => {
    const parsedUrl = url.parse(request.url, true);
    // Allow any path, but require a pin in query parameter
    const pin = parsedUrl.query.pin;

    if (!pin) {
        socket.write('HTTP/1.1 400 Bad Request\r\n\r\n');
        socket.destroy();
        return;
    }

    wss.handleUpgrade(request, socket, head, (ws) => {
        wss.emit('connection', ws, request);
    });
});

wss.on('connection', (ws, request) => {
    const parsedUrl = url.parse(request.url, true);
    const pin = String(parsedUrl.query.pin);
    const username = parsedUrl.query.username || 'Anonymous';
    
    ws.pin = pin;
    ws.username = username;
    ws.isAlive = true;

    // Add to room
    if (!rooms.has(pin)) {
        rooms.set(pin, new Set());
    }
    rooms.get(pin).add(ws);

    // Keepalive ping pong
    ws.on('pong', () => {
        ws.isAlive = true;
    });

    ws.on('message', (message) => {
        try {
            const data = JSON.parse(message);
            // Handle client actions if they send messages (e.g. heartbeat or custom typing status)
            if (data.type === 'ping') {
                ws.send(JSON.stringify({ type: 'pong' }));
            }
        } catch (e) {
            // Ignore malformed text
        }
    });

    ws.on('close', () => {
        const clients = rooms.get(pin);
        if (clients) {
            clients.delete(ws);
            if (clients.size === 0) {
                rooms.delete(pin);
            }
        }
    });

    ws.on('error', (err) => {
        console.error(`WebSocket error for ${username}:`, err);
    });
});

// Setup interval to ping clients and clean up stale connections
const interval = setInterval(() => {
    wss.clients.forEach((ws) => {
        if (ws.isAlive === false) {
            ws.terminate();
            return;
        }
        ws.isAlive = false;
        ws.ping();
    });
}, 30000);

wss.on('close', () => {
    clearInterval(interval);
});

server.listen(PORT, () => {
    console.log(`WebSocket Broker Server running on http://localhost:${PORT}`);
});
