// server.js - Startup entrypoint for cPanel Phusion Passenger / Node.js App
const http = require('http');
const path = require('path');
const fs = require('fs');

const port = process.env.PORT || 3000;
const hostname = '0.0.0.0';

// Check if Next.js standalone server exists
const standaloneServer = path.join(__dirname, '.next', 'standalone', 'server.js');

if (fs.existsSync(standaloneServer)) {
  process.env.PORT = port;
  process.env.HOSTNAME = hostname;
  require(standaloneServer);
} else {
  const next = require('next');
  const app = next({ dev: false, hostname, port });
  const handle = app.getRequestHandler();

  app.prepare().then(() => {
    http.createServer((req, res) => {
      handle(req, res);
    }).listen(port, () => {
      console.log(`> Next.js production server running on port ${port}`);
    });
  }).catch((err) => {
    console.error('Failed to start Next.js server:', err);
    process.exit(1);
  });
}

