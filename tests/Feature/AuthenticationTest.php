<?php

test('unauthenticated user cannot access products')
    ->get('/products')
    ->assertRedirect('login');
