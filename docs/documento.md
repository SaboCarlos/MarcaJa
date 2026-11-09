# MarcaJá — Sistema de Marcação de Consultas

## 1. O sistema

O MarcaJá é uma aplicação web que permite marcar, consultar, confirmar e cancelar consultas médicas numa clínica, sem filas e sem telefonemas. Cada pessoa entra com a sua conta e vê apenas o que lhe diz respeito:

| Perfil | O que pode fazer |
|---|---|
| **Utente** | Criar conta, marcar consultas (para si ou para um familiar), marcar consultas mensais, confirmar presença e cancelar as suas consultas |
| **Secretaria** | Ver as consultas do dia ou de qualquer data, pesquisar pelo telefone do paciente, marcar por quem liga, registar confirmações, cancelar e dar consultas como realizadas |
| **Médico** | Ver a sua agenda por dia, dar consultas como realizadas e cancelar |

## 2. Linguagem e tecnologias

O sistema é escrito em PHP com o framework Laravel, seguindo o padrão MVC (Model–View–Controller).

| Camada | Tecnologia | Função no MarcaJá |
|---|---|---|
| Back-end | PHP 8.3 + Laravel | Rotas, validação dos dados e regras de negócio (`ConsultaController`, `Agenda`) |
| Autenticação | Laravel UI (login e registo clássicos) | Entrada com telefone ou e-mail e três perfis: utente, secretaria e médico |
| Base de dados | SQLite + Eloquent ORM | Tabelas `users`, `medicos` e `consultas`, criadas por migrations e preenchidas por seeders |
| Front-end | Blade, HTML5, Bootstrap 5 e CSS3 | As telas, com design responsivo (computador e telemóvel); o CSS3 acrescenta gradientes, animações, CSS Grid e o modo de letras grandes |
| Interacção | JavaScript | Resumo da marcação em tempo real e bloqueio (riscado) das horas ocupadas e dos dias em que o médico não atende |

## 3. Personas

| | Lúcia Mondlane | Alberto Nhaca | Rosa Chissano |
|---|---|---|---|
| **Perfil** | 34 anos, professora primária, Matola; mãe de um filho de 4 anos | 58 anos, comerciante na Baixa de Maputo; hipertenso | 26 anos, recepcionista da clínica |
| **Conta no MarcaJá** | Utente | Utente | Secretaria |
| **Uso de tecnologia** | Usa o telemóvel todos os dias (WhatsApp, M-Pesa) | Básico: chamadas e mensagens; prefere letras grandes | Bom: trabalha ao computador todo o dia |
| **Objectivo** | Marcar consultas para si e para o filho sem faltar às aulas | Ter a consulta mensal de cardiologia sempre garantida | Ver as consultas do dia e atender quem liga a pedir informação |
| **Frustração actual** | Perde a manhã na fila e às vezes já não há vaga | Liga várias vezes e ninguém atende | Caderno com marcações repetidas e rasuradas |
| **Como o MarcaJá ajuda** | Marca à noite, em casa, em menos de 1 minuto, e escreve o nome do filho na mesma conta | Entra só com o número de telefone e liga as letras grandes (botão A+). O filho marca uma vez a consulta mensal para 3, 6 ou 12 meses, e ele confirma a presença no telemóvel | Abre o dia com as consultas de hoje, pesquisa pelo telefone do paciente e vê o estado de cada consulta |

## 4. Cenários

### Cenário 1 — Lúcia marca uma consulta de pediatria e encontra um horário ocupado

É quinta-feira à noite e o filho da Lúcia tem febre. Ela quer uma consulta para sexta de manhã.

1. Entra no MarcaJá com o seu telefone, 84 123 4567, e a palavra-passe.
2. Na tela Início, filtra por Pediatria. Vê o Dr. Carlos Sitoe e clica em “Marcar com este médico”.
3. Na tela Marcar consulta, o médico já vem escolhido e o telefone já vem preenchido. Escreve o nome do filho, Tomás Mondlane, e escolhe sexta-feira.
4. As 10:30 aparecem riscadas, porque já estão ocupadas. Escolhe as 09:00; o resumo ao lado mostra “Sexta, 13/11/2026 às 09:00”.
5. Confirma. O sistema valida os dados, verifica de novo se a hora está livre e se o médico atende nesse dia, e grava a consulta na conta da Lúcia.
6. Abre a tela Minhas consultas com a mensagem “Consulta marcada com sucesso! Código #0007.”

**Resultado:** consulta marcada em menos de um minuto, sem fila e sem dupla marcação.

### Cenário 2 — Alberto confirma a consulta mensal, depois cancela uma; a Rosa confirma na recepção

O filho do Alberto criou-lhe a conta e marcou a consulta de cardiologia com o Dr. Jorge Tembe, repetida todos os meses durante 3 meses.

1. O Alberto entra no telemóvel só com o seu número, 82 345 6789, e carrega em A+ para ver as letras maiores.
2. Em Minhas consultas vê as 3 consultas mensais com a etiqueta “Marcada”. Na próxima, carrega em “Confirmar presença” e a etiqueta passa a “Confirmada”.
3. Dias depois surge uma viagem. Carrega em “Cancelar” nessa consulta e confirma. A etiqueta passa a “Cancelada” e o horário fica livre para outro paciente; as consultas dos meses seguintes mantêm-se.
4. Mais tarde a Rosa, na recepção, entra com a conta de secretaria e pesquisa o mesmo número quando ele liga. Confirma-lhe que o cancelamento ficou registado e que as outras consultas mensais continuam marcadas.
5. No dia da consulta seguinte, o Dr. Jorge Tembe vê o Alberto na sua agenda de hoje e, no fim, carrega em “Realizada”.

**Resultado:** a vaga é libertada logo, o Alberto não perde as consultas seguintes e a recepção e o médico têm a informação certa, sem cadernos.

## 5. Contas de demonstração

Palavra-passe de todas as contas: `marcaja123`. Para recriar os dados: `php artisan migrate:fresh --seed`.

| Pessoa | Perfil | Entrar com |
|---|---|---|
| Lúcia Mondlane | Utente | 84 123 4567 ou lucia@marcaja.co.mz |
| Alberto Nhaca | Utente | 82 345 6789 ou alberto@marcaja.co.mz |
| Rosa Chissano | Secretaria | 86 765 4321 ou rosa@marcaja.co.mz |
| Dr. Carlos Sitoe | Médico | carlos.sitoe@marcaja.co.mz |
| Dr. Jorge Tembe | Médico | jorge.tembe@marcaja.co.mz |

Os restantes médicos seguem o mesmo formato: `nome.apelido@marcaja.co.mz`.
