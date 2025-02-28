<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Import Pest functions
use function Pest\Laravel\{get, post, put, delete, patch, option};
use function Pest\{expect, test, beforeEach, afterEach, beforeAll, afterAll, it, describe};

// Import Laravel facades
use Illuminate\Support\Facades\{
    App, Artisan, Auth, Blade, Bus, Cache, Config, Cookie, Crypt, DB, 
    Event, File, Gate, Hash, Http, Lang, Log, Mail, Notification, 
    Password, Queue, Redirect, Request, Response, Route, Schema, 
    Session, Storage, URL, Validator, View
}; 