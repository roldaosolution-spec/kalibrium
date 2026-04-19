<?php

// AC-001-01: artisan serve inicia — rota raiz deve retornar HTTP 200
it('[AC-001-01] rota raiz retorna HTTP 200', function (): void {
    $response = $this->get('/');
    $response->assertStatus(200);
});
