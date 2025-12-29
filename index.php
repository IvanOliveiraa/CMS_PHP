<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ESCS Portfólio</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.15); }
        .transition-all { transition: all 0.3s ease; }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <nav class="bg-white shadow p-4 flex justify-evenly items-center sticky top-0 z-10">
        <img
          src="https://www.escs.ipl.pt/sites/default/files/ESCS-Logo.png"
          alt="Logo ESCS"
          class="h-12 w-auto object-contain"
        />
        <a href="admin.php" class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-black transition-all">Admin</a>
    </nav>

    <main class="container mx-auto px-4 py-8">
        <div class="text-center mb-12">
            <h1 class="text-5xl font-bold text-gray-900 mb-4">ESCS Portfólio</h1>
            <p class="text-lg text-gray-600 max-w-3xl mx-auto mb-8">Descobre projetos de alunos da nossa escola</p>
            <div id="searchBox" class="max-w-2xl mx-auto mb-12">
                <input type="text" onkeyup="render(this.value)" placeholder="🔍 Pesquisar projetos..." class="w-full p-4 rounded-lg border shadow-sm focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
        </div>

        <div id="publicGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"></div>
    </main>

    <!-- Modal Leitura -->
    <div id="modal" class="fixed inset-0 bg-black/80 hidden flex items-center justify-center p-4 z-50" onclick="if(event.target==this) this.classList.add('hidden')">
        <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto p-6 relative animate-fade-in">
            <button onclick="document.getElementById('modal').classList.add('hidden')" class="absolute top-4 right-4 text-3xl">&times;</button>
            <div id="modalContent"></div>
        </div>
    </div>

    <script>
        let articles = [];

        async function init() {
            try {
                articles = await (await fetch('api.php')).json();
                render();
            } catch(e) { console.error(e); }
        }

        function render(term = '') {
            const list = term ? articles.filter(a => (a.title+a.description+a.author).toLowerCase().includes(term.toLowerCase())) : articles;
            
            document.getElementById('publicGrid').innerHTML = list.map(a => `
                <div onclick="openModal(${a.id})" class="card bg-white rounded-lg overflow-hidden cursor-pointer transition-all h-full flex flex-col">
                    <img src="${a.cover}" class="w-full h-48 object-cover">
                    <div class="p-4 flex-1 flex flex-col">
                        <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full w-fit mb-2">${a.category}</span>
                        <h3 class="font-bold text-lg mb-2 leading-tight">${a.title}</h3>
                        <p class="text-gray-600 text-sm line-clamp-3 mb-4 flex-1">${a.description}</p>
                        <div class="text-xs text-gray-400 mt-auto flex justify-between">
                            <span>${a.author}</span>
                            <span>${a.completion_date ? new Date(a.completion_date + 'T12:00:00').toLocaleDateString() : ''}</span>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function openModal(id) {
            const a = articles.find(x => x.id == id);
            const yt = a.youtube_url ? (a.youtube_url.match(/(?:v=|youtu\.be\/|\/embed\/)([^&?]+)/)?.[1]) : null;
            
            let html = `<h2 class="text-3xl font-bold mb-4">${a.title}</h2>`;
            if(yt) html += `<iframe src="https://www.youtube.com/embed/${yt}" class="w-full h-[400px] rounded-lg mb-6" allowfullscreen></iframe>`;
            
            if(a.images.length) {
                html += `<div class="flex overflow-x-auto gap-2 mb-6 pb-2 snap-x">${a.images.map(i => 
                    `<img src="${i.path}" class="h-64 rounded shadow snap-center">`
                ).join('')}</div>`;
            }
            
            html += `
                <div class="flex flex-wrap gap-4 text-sm text-gray-500 mb-6 border-b pb-4">
                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded">${a.category}</span>
                    <span>${a.author}</span>
                    ${a.discipline ? `<span>Disciplina: ${a.discipline}</span>` : ''}
                    ${a.completion_date ? `<span>Realizado em: ${new Date(a.completion_date + 'T12:00:00').toLocaleDateString()}</span>` : ''}
                    <span>Publicado: ${new Date(a.created_at).toLocaleDateString()}</span>
                </div>
                <div class="prose max-w-none text-gray-700 whitespace-pre-line mb-8">${a.description}</div>
            `;
            
            if(a.pdf_path) {
                html += `<a href="${a.pdf_path}" target="_blank" class="inline-flex items-center gap-2 bg-red-600 text-white px-6 py-3 rounded hover:bg-red-700 font-bold">📄 Baixar PDF</a>`;
            }
            
            document.getElementById('modalContent').innerHTML = html;
            document.getElementById('modal').classList.remove('hidden');
        }

        init();
    </script>
</body>
</html>
