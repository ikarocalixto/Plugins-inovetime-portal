import os
from tkinter import *
from bs4 import BeautifulSoup


# Função para atualizar o HTML
def atualizar_html(nome_do_produto, novo_nome, nova_descricao, nova_foto, novo_video, nova_lista_fotos, template_html):
    with open(template_html, 'r', encoding='utf-8') as file:
        html_content = file.read()
        soup = BeautifulSoup(html_content, 'lxml')

        # Atualize os elementos com os novos valores
        user_comment = soup.find('div', {'class': 'comment'})
        user_name = soup.find('span', {'class': 'username'})
        user_image = soup.find('img', {'class': 'user-image'})
        user_video_tag = soup.find('video', {'class': 'user-video'})
        user_photos_div = soup.find('div', {'class': 'user-photos'})

        if user_comment:
            user_comment.string = nova_descricao
        if user_name:
            user_name.string = novo_nome
        if user_image:
            user_image['src'] = nova_foto
        if user_video_tag and user_video_tag.find('source'):
            user_video_tag.find('source')['src'] = novo_video
        # Se não existir a div user-photos, cria e insere após o comentário
        if not user_photos_div:
            user_photos_div = soup.new_tag('div', **{'class': 'user-photos'})
            user_comment.insert_after(user_photos_div)
        else:
            user_photos_div.clear()  # Limpa a div se já existir

        # Adiciona as novas imagens à div user-photos
        for url in nova_lista_fotos:
            new_img_tag = soup.new_tag('img', src=url, alt="Foto do produto", **{'class': 'user-photo'})
            user_photos_div.append(new_img_tag)

         


    # Salva as alterações em um novo arquivo
    novo_nome_arquivo = f'{nome_do_produto}.html'
    output_dir = os.path.dirname(template_html)
    caminho_completo = os.path.join(output_dir, novo_nome_arquivo)

    with open(caminho_completo, 'w', encoding='utf-8') as file:
        file.write(str(soup.prettify()))

    print(f"Revisão salva como: {caminho_completo}")

# Função chamada ao submeter o formulário
def submit_form():
    nome_do_produto = entry_produto.get()
    novo_nome = entry_nome.get()
    nova_descricao = entry_descricao.get()
    nova_foto = entry_foto.get()
    novo_video = entry_video.get()
    nova_lista_fotos = entry_fotos_produto.get().split(',')  # Divide a string de URLs em uma lista
    template_html = r'C:\Users\ikaro\OneDrive\Área de Trabalho\pasta do ikaaro3\PLUGINS\comentarios. brand\jadore.html'

    atualizar_html(nome_do_produto, novo_nome, nova_descricao, nova_foto, novo_video, nova_lista_fotos, template_html)

# Criar a interface gráfica
root = Tk()
root.title("Atualizador de HTML")

Label(root, text="Produto:").pack()
entry_produto = Entry(root)
entry_produto.pack()

Label(root, text="Nome:").pack()
entry_nome = Entry(root)
entry_nome.pack()

Label(root, text="Descrição:").pack()
entry_descricao = Entry(root)
entry_descricao.pack()

Label(root, text="Foto Principal (URL):").pack()
entry_foto = Entry(root)
entry_foto.pack()

Label(root, text="Vídeo (URL):").pack()
entry_video = Entry(root)
entry_video.pack()

Label(root, text="Fotos do Produto (URLs, separadas por vírgula):").pack()
entry_fotos_produto = Entry(root)
entry_fotos_produto.pack()

Button(root, text="Atualizar HTML", command=submit_form).pack()

root.mainloop()
