<?php

// AC-001-02: Pest executa (mesmo sem testes reais)
it('verifica que verdadeiro é verdadeiro', function () {
    expect(true)->toBeTrue();
});
