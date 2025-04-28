<?php

session_start();
// $_SESSION = array();
// переменная для быстрого доступа к корню файлов
define("ROOT", dirname(__DIR__));

// данные для бд~
const DB_HOST = 'localhost';
const DB_PORT = '5432';
const DB_NAME = 'opd_lab';
const DB_USERNAME = 'postgres';
const DB_PASSWORD = 'password';

//const DB_TABLE_USERS = 'users';
//const DB_TABLE_TESTS = 'tests';
//const DB_TABLE_TESTINGS = 'testings';
//const DB_TABLE_GENDERS = 'genders';
//const DB_TABLE_ROLES = 'roles';
//const DB_TABLE_PIQS = 'piqs';
//const DB_TABLE_RATINGS = 'ratings';
//const DB_TABLE_PROFESSIONS = 'professions';

const DB_TABLE_USERS = 'opd_users';
const DB_TABLE_TESTS = 'opd_tests';
const DB_TABLE_TESTINGS = 'opd_testings';
const DB_TABLE_PIQS = 'opd_piqs';
const DB_TABLE_RATINGS = 'opd_ratings';
const DB_TABLE_PROFESSIONS = 'opd_professions';
const DB_TABLE_PIQ_LEVEL = 'opd_piq_level';
const DB_TABLE_WEIGHTS = 'opd_weights';
