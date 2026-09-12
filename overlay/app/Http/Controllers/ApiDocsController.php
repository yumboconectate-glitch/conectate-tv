<?php
namespace App\Http\Controllers;
class ApiDocsController extends Controller
{
    public function __invoke(){return view('api-docs',['configured'=>trim((string)env('CONNECTATE_API_TOKEN',''))!=='']);}
}
