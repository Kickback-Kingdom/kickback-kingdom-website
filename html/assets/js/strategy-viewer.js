(() => {
    const canvas = document.getElementById('strategy-canvas');
    const ctx = canvas.getContext('2d');
    const addPrereqBtn = document.getElementById('add-prerequisite');

    let nodes = [];
    let edges = [];
    let nextId = 1;

    let scale = 1;
    let offsetX = 0, offsetY = 0;
    let draggingNode = null;
    let dragStart = { x: 0, y: 0, offsetX: 0, offsetY: 0, nodeOffsetX: 0, nodeOffsetY: 0 };
    let panning = false;
    let connectFrom = null;
    let prereqMode = false;

    function worldToScreen(x, y) {
        return { x: x * scale + offsetX, y: y * scale + offsetY };
    }

    function screenToWorld(x, y) {
        return { x: (x - offsetX) / scale, y: (y - offsetY) / scale };
    }

    function drawGrid() {
        const step = 50 * scale;
        ctx.beginPath();
        ctx.strokeStyle = '#eee';
        ctx.lineWidth = 1;
        for (let x = offsetX % step; x < canvas.width; x += step) {
            ctx.moveTo(x, 0);
            ctx.lineTo(x, canvas.height);
        }
        for (let y = offsetY % step; y < canvas.height; y += step) {
            ctx.moveTo(0, y);
            ctx.lineTo(canvas.width, y);
        }
        ctx.stroke();
    }

    function wrapText(text, x, y, maxWidth, lineHeight) {
        const words = text.split(' ');
        let line = '';
        for (let n = 0; n < words.length; n++) {
            const testLine = line + words[n] + ' ';
            const metrics = ctx.measureText(testLine);
            if (metrics.width > maxWidth && n > 0) {
                ctx.fillText(line, x, y);
                line = words[n] + ' ';
                y += lineHeight;
            } else {
                line = testLine;
            }
        }
        ctx.fillText(line, x, y);
    }

    function draw() {
        ctx.save();
        ctx.setTransform(1, 0, 0, 1, 0, 0);
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        drawGrid();
        ctx.setTransform(scale, 0, 0, scale, offsetX, offsetY);
        edges.forEach(e => {
            const from = nodes.find(n => n.id === e.from);
            const to = nodes.find(n => n.id === e.to);
            if (from && to) {
                ctx.beginPath();
                ctx.moveTo(from.x + from.w / 2, from.y + from.h / 2);
                ctx.lineTo(to.x + to.w / 2, to.y + to.h / 2);
                ctx.strokeStyle = '#555';
                ctx.lineWidth = 1 / scale;
                ctx.stroke();
            }
        });
        nodes.forEach(n => {
            ctx.fillStyle = '#fff';
            ctx.strokeStyle = '#000';
            ctx.lineWidth = 1 / scale;
            ctx.fillRect(n.x, n.y, n.w, n.h);
            ctx.strokeRect(n.x, n.y, n.w, n.h);
            ctx.fillStyle = '#000';
            ctx.font = `${12 / scale}px sans-serif`;
            wrapText(n.title || `Node ${n.id}`, n.x + 4 / scale, n.y + 12 / scale, n.w - 8 / scale, 12 / scale);
        });
        ctx.restore();
    }

    function hitNode(pos) {
        for (let i = nodes.length - 1; i >= 0; i--) {
            const n = nodes[i];
            if (pos.x >= n.x && pos.x <= n.x + n.w && pos.y >= n.y && pos.y <= n.y + n.h) {
                return n;
            }
        }
        return null;
    }

    canvas.addEventListener('dblclick', e => {
        const pos = screenToWorld(e.offsetX, e.offsetY);
        nodes.push({ id: 'n' + nextId++, x: pos.x - 50, y: pos.y - 25, w: 100, h: 50, title: 'New Node' });
        draw();
    });

    canvas.addEventListener('pointerdown', e => {
        const world = screenToWorld(e.offsetX, e.offsetY);
        const node = hitNode(world);
        dragStart = { x: e.clientX, y: e.clientY, offsetX, offsetY, nodeOffsetX: world.x - (node ? node.x : 0), nodeOffsetY: world.y - (node ? node.y : 0) };
        if (e.button === 1 || e.button === 2) {
            panning = true;
        } else if (node && e.button === 0 && !e.shiftKey) {
            draggingNode = node;
        }
        canvas.setPointerCapture(e.pointerId);
    });

    canvas.addEventListener('pointermove', e => {
        const dx = e.clientX - dragStart.x;
        const dy = e.clientY - dragStart.y;
        if (draggingNode) {
            const world = screenToWorld(e.offsetX, e.offsetY);
            draggingNode.x = world.x - dragStart.nodeOffsetX;
            draggingNode.y = world.y - dragStart.nodeOffsetY;
            draw();
        } else if (panning) {
            offsetX = dragStart.offsetX + dx;
            offsetY = dragStart.offsetY + dy;
            draw();
        }
    });

    canvas.addEventListener('pointerup', e => {
        draggingNode = null;
        panning = false;
        canvas.releasePointerCapture(e.pointerId);
    });

    canvas.addEventListener('click', e => {
        const world = screenToWorld(e.offsetX, e.offsetY);
        const node = hitNode(world);
        if (!node) return;
        if (prereqMode) {
            if (connectFrom && connectFrom !== node) {
                edges.push({ id: 'e' + Date.now(), from: connectFrom.id, to: node.id });
                connectFrom = null;
                prereqMode = false;
                addPrereqBtn.classList.remove('active');
                draw();
            } else {
                connectFrom = node;
            }
            return;
        }
        if (!e.shiftKey) return;
        if (connectFrom && connectFrom !== node) {
            edges.push({ id: 'e' + Date.now(), from: connectFrom.id, to: node.id });
            connectFrom = null;
            draw();
        } else {
            connectFrom = node;
        }
    });

    canvas.addEventListener('contextmenu', e => e.preventDefault());

    canvas.addEventListener('wheel', e => {
        const factor = e.deltaY < 0 ? 1.1 : 0.9;
        const world = screenToWorld(e.offsetX, e.offsetY);
        scale *= factor;
        offsetX = e.offsetX - world.x * scale;
        offsetY = e.offsetY - world.y * scale;
        draw();
    });

    function exportJSON() {
        const data = { nodes, edges };
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'diagram.json';
        a.click();
        URL.revokeObjectURL(url);
    }

    document.getElementById('export-json').addEventListener('click', exportJSON);

    document.getElementById('add-milestone').addEventListener('click', () => {
        const center = screenToWorld(canvas.width / 2, canvas.height / 2);
        nodes.push({ id: 'n' + nextId++, x: center.x - 50, y: center.y - 25, w: 100, h: 50, title: 'New Milestone' });
        draw();
    });

    addPrereqBtn.addEventListener('click', () => {
        prereqMode = !prereqMode;
        connectFrom = null;
        addPrereqBtn.classList.toggle('active', prereqMode);
    });

    document.getElementById('import-json').addEventListener('change', e => {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = () => {
            try {
                const obj = JSON.parse(reader.result);
                nodes = obj.nodes || [];
                edges = obj.edges || [];
                nextId = nodes.reduce((max, n) => Math.max(max, parseInt(n.id.replace('n', ''), 10)), 0) + 1;
                draw();
            } catch (err) {
                alert('Invalid JSON');
            }
        };
        reader.readAsText(file);
    });

    draw();
})();
