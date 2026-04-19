<?php

// AC-001-02: Pest executa (mesmo sem testes reais)
it('retorna HTTP 200 na rota raiz', function () {
    $response = $this->get('/');
    $response->assertStatus(200);
});
