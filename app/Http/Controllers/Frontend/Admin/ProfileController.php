<?php

namespace App\Http\Controllers\Frontend\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

/** Chỉ render trang. */
class ProfileController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Admin/Profile');
    }
}
