<?php

it('responde el health check', function () {
    $this->get('/up')->assertOk();
});
