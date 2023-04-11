const http = require("http");
const server = http.createServer((req, res) => {
    res.writeHead(200, { "Content-Type": "text/html" });
    res.end("<h1>Backend (Node.js)</h1>");
});
 
server.listen(80, () => {
  console.log(`Server is running`);
});

