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
    <title>Editar Artigo - CMS Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .upload-area { border: 2px dashed #d1d5db; border-radius: 0.5rem; transition: all 0.3s ease; cursor: pointer; text-align: center; padding: 2rem; }
        .upload-area:hover { border-color: #9ca3af; background-color: #f9fafb; }
        .input-field { width: 100%; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; outline: none; transition: border-color 0.2s; }
        .input-field:focus { border-color: #2563eb; ring: 2px solid #2563eb; }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <nav class="bg-white shadow p-4 flex justify-between items-center sticky top-0 z-10 mb-8">
        <div class="flex items-center gap-4">
            <a href="admin.php" class="text-gray-600 hover:text-gray-900 font-bold text-xl">← Voltar</a>
            <h1 class="text-xl font-bold border-l pl-4 border-gray-300">Editor de Publicação</h1>
        </div>
        <div class="flex gap-2">
            <a href="index.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition-all">Ver Site</a>
            <a href="login.php?logout=1" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition-all">Sair</a>
        </div>
    </nav>

    <main class="container mx-auto px-4 pb-12">
        <div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow-lg">
            
            <form id="form" onsubmit="saveArticle(event)" class="space-y-4 mb-8">
                <input type="hidden" name="id">
                <div>
                    <input name="title" required placeholder="Título" class="input-field">
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-2 font-bold">Alunos</label>
                    <div id="authors-wrapper" class="space-y-2"></div>
                    <button type="button" onclick="addAuthorField()" class="mt-2 text-sm text-blue-600 hover:underline font-medium">+ Adicionar Aluno</button>
                </div>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Data de Realização</label>
                        <input name="completion_date" type="date" class="input-field">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Disciplina</label>
                        <input name="discipline" placeholder="Disciplina" class="input-field">
                    </div>
                </div>
                <textarea name="description" required rows="3" placeholder="Descrição" class="input-field"></textarea>
                <div class="grid md:grid-cols-2 gap-4">
                    <select name="category" class="input-field">
                        <option>Audiovisual e Multimédia</option><option>Jornalismo</option><option>Publicidade e Marketing</option><option>Relações Públicas e Comunicação Empresarial</option>
                    <input name="youtubeUrl" placeholder="URL do YouTube" class="input-field">
                </div>
                
                <div class="grid md:grid-cols-2 gap-4 border-t pt-4">
                    <!-- Upload Capa -->
                    <div>
                        <label class="block text-sm font-bold mb-2">Imagem de Capa</label>
                        <div class="upload-area" onclick="document.getElementById('coverInput').click()">
                            <input type="file" id="coverInput" accept="image/*" class="hidden" onchange="previewCover(this)">
                            <div id="coverPlaceholder">
                                <p class="text-sm text-blue-600 font-medium">Selecionar Capa</p>
                            </div>
                            <div id="coverPreview" class="hidden mt-2"></div>
                        </div>
                    </div>

                    <!-- Upload Carrossel -->
                    <div>
                        <label class="block text-sm font-bold mb-2">Imagens em Carrossel</label>
                        <div class="upload-area" onclick="document.getElementById('carouselInput').click()">
                            <input type="file" id="carouselInput" multiple accept="image/*" class="hidden" onchange="previewCarousel(this)">
                            <div id="carouselPlaceholder">
                                <p class="text-sm text-blue-600 font-medium">Selecionar Fotos</p>
                            </div>
                            <div id="carouselPreview" class="hidden grid grid-cols-3 gap-2 mt-2"></div>
                        </div>
                    </div>
                    
                    <!-- Upload PDF -->
                    <div>
                        <label class="block text-sm font-bold mb-2">PDF (Documento)</label>
                        <div class="upload-area" onclick="document.getElementById('pdfInput').click()">
                            <input type="file" id="pdfInput" name="pdfFile" accept="application/pdf" class="hidden" onchange="previewPdf(this)">
                            <div id="pdfPlaceholder">
                                <p class="text-sm text-blue-600 font-medium">Upload PDF</p>
                            </div>
                            <div id="pdfPreview" class="hidden mt-2 text-sm font-bold text-gray-700"></div>
                        </div>
                    </div>
                </div>

                <!-- Imagens Existentes -->
                <div id="currentImages" class="flex gap-2 flex-wrap mt-2"></div>
                
                <div class="flex gap-2 pt-2">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex-1">Salvar</button>
                    <button type="button" onclick="location.href='admin.php'" class="bg-gray-300 text-gray-700 px-6 py-2 rounded hover:bg-gray-400">Cancelar</button>
                </div>
            </form>
        </div>
    </main>

    <script>
        let currentArticleId = null;

        async function init() {
            const urlParams = new URLSearchParams(window.location.search);
            currentArticleId = urlParams.get('id');

            if (currentArticleId) {
                try {
                    const articles = await (await fetch('api.php')).json();
                    const a = articles.find(x => x.id == currentArticleId);
                    if (a) populateForm(a);
                    else addAuthorField();
                } catch(e) { console.error(e); addAuthorField(); }
            } else {
                addAuthorField();
            }
        }

        function populateForm(a) {
            const form = document.getElementById('form');
            // Map DB column youtube_url to form field youtubeUrl
            a.youtubeUrl = a.youtube_url;
            ['id','title','description','category','youtubeUrl','completion_date','discipline'].forEach(f => form[f].value = a[f] || '');
            
            const wrapper = document.getElementById('authors-wrapper');
            wrapper.innerHTML = '';
            (a.author || '').split(', ').forEach(auth => addAuthorField(auth));
            if(!wrapper.children.length) addAuthorField();

            const box = document.getElementById('currentImages');
            box.innerHTML = a.images.map(img => `
                <div class="relative group w-16 h-16">
                    <img src="${img.path}" class="w-full h-full object-cover rounded">
                    <button type="button" onclick="delImg(${img.id}, ${a.id})" class="absolute inset-0 bg-red-500/80 text-white opacity-0 group-hover:opacity-100 flex items-center justify-center rounded transition-opacity">X</button>
                </div>
            `).join('');

            if(a.pdf_path) {
                document.getElementById('pdfPlaceholder').classList.add('hidden');
                document.getElementById('pdfPreview').classList.remove('hidden');
                document.getElementById('pdfPreview').innerHTML = `
                    <div class="flex items-center justify-between bg-gray-50 p-2 rounded border">
                        <a href="${a.pdf_path}" target="_blank" class="flex items-center gap-2 text-blue-600 hover:underline truncate">
                            <span>📄 Visualizar PDF</span>
                        </a>
                        <button type="button" onclick="delPdf(${a.id})" class="text-red-500 hover:text-red-700 font-bold ml-2">X</button>
                    </div>
                `;
            }
        }

        async function saveArticle(e) {
            e.preventDefault();
            const btn = e.target.querySelector('button[type=submit]');
            const oldTxt = btn.innerText;
            btn.innerText = 'Salvando...'; btn.disabled = true;

            const fd = new FormData(e.target);
            const cover = document.getElementById('coverInput').files[0];
            if(cover) fd.append('images[]', cover);
            
            const carousel = document.getElementById('carouselInput').files;
            if(carousel.length) Array.from(carousel).forEach(f => fd.append('images[]', f));

            const res = await fetch('api.php', { method: 'POST', body: fd });
            if (res.status === 401) { alert('Sessão expirada. Faça login novamente.'); location.href='login.php'; return; }
            
            alert('Salvo com sucesso!');
            location.href = 'admin.php';
        }

        async function delImg(imgId, artId) {
            if(confirm('Excluir esta imagem?')) {
                const res = await fetch(`api.php?type=image&id=${imgId}`, { method: 'DELETE' });
                if (res.status === 401) return location.href='login.php';
                // Reload current article data
                init();
            }
        }

        async function delPdf(id) {
            if(confirm('Remover o PDF deste artigo?')) {
                const res = await fetch(`api.php?type=pdf&id=${id}`, { method: 'DELETE' });
                if (res.status === 401) return location.href='login.php';
                document.getElementById('pdfPlaceholder').classList.remove('hidden');
                document.getElementById('pdfPreview').classList.add('hidden');
                document.getElementById('pdfPreview').innerHTML = '';
            }
        }

        function previewCover(input) {
            if (input.files[0]) {
                document.getElementById('coverPlaceholder').classList.add('hidden');
                document.getElementById('coverPreview').classList.remove('hidden');
                const r = new FileReader();
                r.onload = e => document.getElementById('coverPreview').innerHTML = `<img src="${e.target.result}" class="w-full h-32 object-cover rounded shadow">`;
                r.readAsDataURL(input.files[0]);
            }
        }

        function previewCarousel(input) {
            if (input.files.length) {
                document.getElementById('carouselPlaceholder').classList.add('hidden');
                const p = document.getElementById('carouselPreview');
                p.classList.remove('hidden'); p.innerHTML = '';
                Array.from(input.files).forEach(f => {
                    const r = new FileReader();
                    r.onload = e => p.innerHTML += `<img src="${e.target.result}" class="w-full h-24 object-cover rounded shadow">`;
                    r.readAsDataURL(f);
                });
            }
        }

        function previewPdf(input) {
            if (input.files[0]) {
                document.getElementById('pdfPlaceholder').classList.add('hidden');
                const p = document.getElementById('pdfPreview');
                p.classList.remove('hidden'); p.textContent = '📄 ' + input.files[0].name;
            }
        }

        function addAuthorField(val = '') {
            const div = document.createElement('div');
            div.className = 'flex gap-2';
            div.innerHTML = `
                <input name="authors[]" value="${val}" placeholder="Nome do Aluno" class="input-field" required>
                <button type="button" onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700 font-bold px-2">X</button>
            `;
            document.getElementById('authors-wrapper').appendChild(div);
        }

        init();
    </script>
</body>
</html>
