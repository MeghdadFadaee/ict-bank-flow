<?php

it('reports service health', function () {
    $this->getJson('/health')
        ->assertSuccessful()
        ->assertExactJson(['status' => 'UP']);
});
