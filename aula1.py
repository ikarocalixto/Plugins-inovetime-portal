
import openai

# Substitua 'sua-chave-api-aqui' pela sua chave de API real.
openai.api_key = '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'

def chat_with_gpt3(prompt):
    try:
        response = openai.Completion.create(
            engine="text-davinci-003",  # ou qualquer outra versão do modelo GPT
            prompt=prompt,
            max_tokens=100
        )
        return response.choices[0].text.strip()
    except Exception as e:
        print("Erro ao obter resposta do GPT:", e)
        return None

# Inicialize a conversa
print("Bem-vindo à conversa com o ChatGPT!")
while True:
    user_input = input("Você: ")
    if user_input.lower() == 'sair':
        print("ChatGPT: Até logo!")
        break

    response = chat_with_gpt3(user_input)
    print("ChatGPT:", response)
