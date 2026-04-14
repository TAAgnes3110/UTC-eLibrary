<?php

namespace Tests\Feature\Frontend;

use Tests\TestCase;

class ReaderSiteTest extends TestCase
{
    public function test_reader_home_returns_ok(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_reader_static_pages_return_ok(): void
    {
        $this->get('/gioi-thieu')->assertOk();
        $this->get('/quy-dinh-thu-vien')->assertRedirect('/quy-dinh/muon-sach');
        $this->get('/quy-dinh')->assertOk();
        $this->get('/quy-dinh/thu-tuc-lam-the')->assertOk();
        $this->get('/quy-dinh/lich-phuc-vu')->assertOk();
        $this->get('/quy-dinh/muon-sach')->assertOk();
        $this->get('/tra-cuu-sach')->assertOk();
        $this->get('/tra-cuu-sach/999999999')->assertNotFound();
        $this->get('/dich-vu')->assertOk();
    }
}
