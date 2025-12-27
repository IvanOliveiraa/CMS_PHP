<?php
session_start();
if (empty($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.15); }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <nav class="bg-white shadow p-4 flex justify-evenly items-center sticky top-0 z-10">
       <img
          src="https://www.escs.ipl.pt/sites/default/files/ESCS-Logo.png"
          alt="Logo ESCS"
          class="h-14 w-auto object-contain"
        />
        <div class="flex gap-2">
            <a href="index.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition-all">Ver Site</a>
            <a href="login.php?logout=1" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition-all">Sair</a>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-8">
        <div class="text-center mb-12">
            <h1 class="text-5xl font-bold text-gray-900 mb-4">Gerenciar Publicações</h1>
            <p class="text-lg text-gray-600 max-w-3xl mx-auto mb-8">Adicione, edite ou remova artigos da biblioteca digital</p>
            <div class="max-w-3xl mx-auto mb-12 flex gap-4">
                <input type="text" onkeyup="renderList(this.value)" placeholder="🔍 Buscar artigos..." class="w-full p-4 rounded-lg border shadow-sm focus:ring-2 focus:ring-blue-500 outline-none">
                <a href="article_form.php" class="bg-green-600 text-white px-8 py-4 rounded-lg hover:bg-green-700 font-bold shadow-sm transition-all whitespace-nowrap flex items-center">
                    + Nova publicação
                </a>
            </div>
        </div>

        <div id="adminList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"></div>
    </main>

    <script>
        let articles = [];

        async function init() {
            try {
                articles = await (await fetch('api.php')).json();
                renderList();
            } catch(e) { console.error(e); }
        }

        function renderList(term = '') {
            const list = term ? articles.filter(a => (a.title+a.description+a.author+a.category).toLowerCase().includes(term.toLowerCase())) : articles;
            
            document.getElementById('adminList').innerHTML = list.map(a => `
                <div class="card bg-white rounded-lg overflow-hidden shadow-lg transition-all h-full flex flex-col">
                    <img src="${a.cover}" class="w-full h-48 object-cover">
                    <div class="p-4 flex-1 flex flex-col">
                        <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full w-fit mb-2">${a.category}</span>
                        <h3 class="font-bold text-lg mb-2 leading-tight">${a.title}</h3>
                        <p class="text-gray-600 text-sm line-clamp-3 mb-4 flex-1">${a.description}</p>
                        <div class="text-xs text-gray-400 mt-auto flex justify-between mb-4">
                            <span>${a.author}</span>
                            <span>${new Date(a.created_at).toLocaleDateString()}</span>
                        </div>
                        <div class="flex gap-2 border-t pt-4">
                            <a href="article_form.php?id=${a.id}" class="flex-1 bg-blue-50 text-blue-600 py-2 rounded hover:bg-blue-100 font-medium transition-colors text-center">Editar</a>
                            <button onclick="del(${a.id})" class="flex-1 bg-red-50 text-red-600 py-2 rounded hover:bg-red-100 font-medium transition-colors">Excluir</button>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        async function del(id) {
            if(confirm('Excluir este artigo permanentemente?')) {
                const res = await fetch(`api.php?id=${id}`, { method: 'DELETE' });
                if (res.status === 401) return location.href='login.php';
                init();
            }
        }

        init();
    </script>
</body>
</html>
