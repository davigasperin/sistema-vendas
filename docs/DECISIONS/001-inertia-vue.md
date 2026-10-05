# ADR-001: Inertia.js + Vue 3 para Frontend

## Status

Aceito

## Contexto

O sistema possui interface web com Blade + Alpine.js + JavaScript imperativo em `public/js/`. O Blade não oferece reatividade para telas complexas como criação de venda com itens dinâmicos e cálculos em tempo real. A alternativa SPA completa (Vue standalone + API interna) duplicaria endpoints e exigiria gerenciamento de estado de autenticação separado.

## Decisão

Adotar **Inertia.js com Vue 3 e TypeScript** para toda a interface web, mantendo a API REST `/api/v1` separada para integrações externas.

## Consequências

### Positivas
- Não há camada API interna desnecessária — Laravel controla rotas, auth e validação
- Tipagem de dados do servidor para o cliente via Inertia props
- Migração incremental Blade → Vue possível tela por tela
- Navegação SPA com server-side redirects e flash messages nativos

### Negativas
- Interface web depende de PHP para renderizar dados (sem estáticos puros)
- Requer middleware `HandleInertiaRequests` para dados compartilhados

## Alternativas Consideradas

1. **Blade + Alpine.js**: Manter como está. Rejeitado por falta de reatividade para formulários complexos.
2. **SPA Vue standalone + API interna**: Rejeitado — duplicaria endpoints, exigiria state management para sessão e CSRF, complexidade desnecessária.
3. **Livewire**: Rejeitado — menos maduro para formulários complexos, round-trips por interação.
