<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\lib\jDateTime;

class ApiController extends Controller
{

    private function microtime_float()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    /**
     * Converts bytes to B, KB , MB, ..., YB
     *
     * @param $bytes
     * @param int $precision
     * @param string $dec_point
     * @param string $thousands_sep
     * @return string
     */
    private function formatBytes($bytes, $precision = 2, $dec_point = '.', $thousands_sep = ',')
    {
        $negative = $bytes < 0;
        if ($negative) $bytes *= -1;
        $size = $bytes;
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $power = $size > 0 ? floor(log($size, 1024)) : 0;
        $sz = $size / pow(1024, $power);
        if ($sz - round($sz) == 0) $precision = 0;
        if ($negative) $sz *= -1;
        return number_format($sz, $precision, $dec_point, $thousands_sep) . ' ' . $units[$power];
    }

    private function date_convert($date)
    {

        // If user's input has Arabic/Persian numbers, we change it to standard english numbers
        $persian_numbers = [
            '۰' => '0', '٠' => '0',
            '۱' => '1', '١' => '1',
            '۲' => '2', '٢' => '2',
            '۳' => '3', '٣' => '3',
            '۴' => '4', '٤' => '4',
            '۵' => '5', '٥' => '5',
            '۶' => '6', '٦' => '6',
            '۷' => '7', '٧' => '7',
            '۸' => '8', '٨' => '8',
            '۹' => '9', '٩' => '9',
        ];

        return strtr($date, $persian_numbers);
    }

    private function get_string_between($string, $start, $end)
    {
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0) return '';
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }

    public function end_points()
    {
        return response()->json([
            'meta' =>
                [
                    'code' => 200,
                    'message' => 'OK'
                ],
            'data' =>
                [
                    'end_points' =>
                        [
                            'v1' => [
                                '/v1/student_schedule',
                                '/v1/internet_credit',
                                '/v1/self_service_credits',
                                '/v1/self_service_menu',
                                '/v1/exams',
                                '/v1/library',
                            ],
                            'v2' => [
                                '/v2/stu/profile',
                                '/v2/stu/schedule',
                                '/v2/stu/exam_card'
                            ]
                        ],
                    'source' => 'https://github.com/sut-it/Sadjad-API',
                    'manual' => 'https://github.com/sut-it/Sadjad-API#current-end-points',
                    'privacy' => 'https://github.com/sut-it/Sadjad-API#important-privacy-note',
                    'licence' => 'https://github.com/sut-it/Sadjad-API#license',
                ]
        ], 200);
    }


    public function v2_stu_exam_card(Request $request)
    {
        $errors = [];
        $time_start = $this->microtime_float();
        if (! $request->input('username')){
            $errors[] = 'username is not provided.';
        }
        if (! $request->input('password')){
            $errors[] = 'password is not provided.';
        }
        if (count($errors)) {
            return response()->json([
                'meta' =>
                    [
                        'code' => 400,
                        'message' => 'Bad Request',
                        'error' => $errors
                    ]
            ], 400);
        }

        // If user's input has Arabic/Persian numbers, we change it to standard english numbers
        $persian_numbers = [
            '۰' => '0', '٠' => '0',
            '۱' => '1', '١' => '1',
            '۲' => '2', '٢' => '2',
            '۳' => '3', '٣' => '3',
            '۴' => '4', '٤' => '4',
            '۵' => '5', '٥' => '5',
            '۶' => '6', '٦' => '6',
            '۷' => '7', '٧' => '7',
            '۸' => '8', '٨' => '8',
            '۹' => '9', '٩' => '9',
        ];

        $auth = http_build_query([
            'StID' => strtr($request->input('username'), $persian_numbers),
            'UserPassword' => strtr($request->input('password'), $persian_numbers)
        ]);

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, 'http://stu.sadjad.ac.ir/Interim.php');
        curl_setopt($ch,CURLOPT_POST,2);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $auth);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_COOKIESESSION, 1);
        curl_setopt($ch, CURLOPT_COOKIEFILE, '-');
        curl_setopt($ch, CURLOPT_COOKIEJAR, '-');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.84 Safari/537.36';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_exec($ch);
        curl_setopt($ch,CURLOPT_URL, 'http://stu.sadjad.ac.ir/strcss/StExamDaysSpecForm.php');
        $result = curl_exec($ch);
        $dom = new \domDocument;
        @$dom->loadHTML($result);
        if (strpos($dom->textContent, ' درخواستبنا به دلایل امنیتی ادامه استفاده شما از سیستم منوط به ورود مجدد به سیستم استلطفا برای ورود مجدد ب')){
            $time_end = $this->microtime_float();
            $time = $time_end - $time_start;

            return response()->json([
                'meta' =>
                    [
                        'code' => 403,
                        'message' => 'Forbidden',
                        'connect_time' => $time
                    ],
            ], 403);
        }

        $file = app()->basePath('public/static/') . sha1($request->input('username') . $request->input('username')) . '.pdf';
        if (!
            ( file_exists($file) && time() > filemtime ($file) + 31 * 24 * 60 * 60 ) ||
            ! file_exists($file)
        ) {
            @unlink($file);

            $result = '<style>@font-face{font-family: IRANSans; font-style: normal; font-weight: bold; src: url(\'https://acm.sadjad.ac.ir/dist/fonts/eot/IRANSansWeb_Bold.eot\'); src: url(\'https://acm.sadjad.ac.ir/dist/fonts/eot/IRANSansWeb_Boldd41d.eot?#iefix\') format(\'embedded-opentype\'), /* IE6-8 */ url(\'https://acm.sadjad.ac.ir/dist/fonts/woff2/IRANSansWeb_Bold.woff2\') format(\'woff2\'), /* FF39+,Chrome36+, Opera24+*/ url(\'https://acm.sadjad.ac.ir/dist/fonts/woff/IRANSansWeb_Bold.woff\') format(\'woff\'), /* FF3.6+, IE9, Chrome6+, Saf5.1+*/ url(\'https://acm.sadjad.ac.ir/dist/fonts/ttf/IRANSansWeb_Bold.ttf\') format(\'truetype\');}@font-face{font-family: IRANSans; font-style: normal; font-weight: 500; src: url(\'https://acm.sadjad.ac.ir/dist/fonts/eot/IRANSansWeb_Medium.eot\'); src: url(\'https://acm.sadjad.ac.ir/dist/fonts/eot/IRANSansWeb_Mediumd41d.eot?#iefix\') format(\'embedded-opentype\'), /* IE6-8 */ url(\'https://acm.sadjad.ac.ir/dist/fonts/woff2/IRANSansWeb_Medium.woff2\') format(\'woff2\'), /* FF39+,Chrome36+, Opera24+*/ url(\'https://acm.sadjad.ac.ir/dist/fonts/woff/IRANSansWeb_Medium.woff\') format(\'woff\'), /* FF3.6+, IE9, Chrome6+, Saf5.1+*/ url(\'https://acm.sadjad.ac.ir/dist/fonts/ttf/IRANSansWeb_Medium.ttf\') format(\'truetype\');}@font-face{font-family: IRANSans; font-style: normal; font-weight: 300; src: url(\'https://acm.sadjad.ac.ir/dist/fonts/eot/IRANSansWeb_Light.eot\'); src: url(\'https://acm.sadjad.ac.ir/dist/fonts/eot/IRANSansWeb_Lightd41d.eot?#iefix\') format(\'embedded-opentype\'), /* IE6-8 */ url(\'https://acm.sadjad.ac.ir/dist/fonts/woff2/IRANSansWeb_Light.woff2\') format(\'woff2\'), /* FF39+,Chrome36+, Opera24+*/ url(\'https://acm.sadjad.ac.ir/dist/fonts/woff/IRANSansWeb_Light.woff\') format(\'woff\'), /* FF3.6+, IE9, Chrome6+, Saf5.1+*/ url(\'https://acm.sadjad.ac.ir/dist/fonts/ttf/IRANSansWeb_Light.ttf\') format(\'truetype\');}@font-face{font-family: IRANSans; font-style: normal; font-weight: 200; src: url(\'https://acm.sadjad.ac.ir/dist/fonts/eot/IRANSansWeb_UltraLight.eot\'); src: url(\'https://acm.sadjad.ac.ir/dist/fonts/eot/IRANSansWeb_UltraLightd41d.eot?#iefix\') format(\'embedded-opentype\'), /* IE6-8 */ url(\'https://acm.sadjad.ac.ir/dist/fonts/woff2/IRANSansWeb_UltraLight.woff2\') format(\'woff2\'), /* FF39+,Chrome36+, Opera24+*/ url(\'https://acm.sadjad.ac.ir/dist/fonts/woff/IRANSansWeb_UltraLight.woff\') format(\'woff\'), /* FF3.6+, IE9, Chrome6+, Saf5.1+*/ url(\'https://acm.sadjad.ac.ir/dist/fonts/ttf/IRANSansWeb_UltraLight.ttf\') format(\'truetype\');}@font-face{font-family: IRANSans; font-style: normal; font-weight: normal; src: url(\'https://acm.sadjad.ac.ir/dist/fonts/eot/IRANSansWeb.eot\'); src: url(\'https://acm.sadjad.ac.ir/dist/fonts/eot/IRANSansWebd41d.eot?#iefix\') format(\'embedded-opentype\'), /* IE6-8 */ url(\'https://acm.sadjad.ac.ir/dist/fonts/woff2/IRANSansWeb.woff2\') format(\'woff2\'), /* FF39+,Chrome36+, Opera24+*/ url(\'https://acm.sadjad.ac.ir/dist/fonts/woff/IRANSansWeb.woff\') format(\'woff\'), /* FF3.6+, IE9, Chrome6+, Saf5.1+*/ url(\'https://acm.sadjad.ac.ir/dist/fonts/ttf/IRANSansWeb.ttf\') format(\'truetype\');}*{font-family: IRANSans, Tahoma, "Helvetica Neue", Helvetica, Arial, sans-serif!important;}</style>'
                . $result;
            $profile_pic_url =  $this->get_string_between($result, '<img style=\'height:135; width:105; top:20; left :40; position:absolute;\' src="', '"></img>');
            $login = [
                'username' => strtr($request->input('username'), $persian_numbers),
                'password' => strtr($request->input('password'), $persian_numbers)
            ];
            $all = file_get_contents('https://api.sadjad.ac.ir/v2/stu/profile?' . http_build_query($login));
            $json = json_decode($all);

            if ( $json->meta->message == 'OK' ) {
                $profile_pic = $json->data->profile_picture->public_url;
            } else {
                $time_end = $this->microtime_float();
                $time = $time_end - $time_start;
                return response()->json([
                    'meta' =>
                        [
                            'code' => 403,
                            'message' => 'Forbidden',
                            'connect_time' => $time
                        ],
                ], 403);
            }

            $result = str_replace('/rcssimgs/logo.gif', 'http://stu.sadjad.ac.ir/rcssimgs/logo.gif', $result);
            $result = str_replace($profile_pic_url, $profile_pic, $result);
            $result = str_replace('/rcssimgs/stamp.gif', 'http://stu.sadjad.ac.ir/rcssimgs/stamp.gif', $result);
            $result = str_replace('<br><br><br><br><br><br><br>', '', $result);

            $data = array('convert' => '',
                'html' => $result
            );

            $options = array(
                'http' => array(
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method'  => 'POST',
                    'content' => http_build_query($data),
                ),
            );
            $context  = stream_context_create($options);
            $result = file_get_contents('http://freehtmltopdf.com', false, $context);

            file_put_contents($file, $result);

            $PDF_public_url = [
                'public_url' => app('url')->asset('static/' . sha1($request->input('username') . $request->input('username')) . '.pdf'),
                'cache' => 'MISS',
                'cache_expires_at' => filemtime ($file) + 31 * 24 * 60 * 60
            ];
        } else {
            $PDF_public_url = [
                'public_url' => app('url')->asset('static/' . sha1($request->input('username') . $request->input('username')) . '.pdf'),
                'cache' => 'HIT',
                'cache_expires_at' => filemtime ($file) + 31 * 24 * 60 * 60
            ];
        }

        $time_end = $this->microtime_float();
        $time = $time_end - $time_start;
        return response()->json([
            'meta' =>
                [
                    'code' => 200,
                    'message' => 'OK',
                    'connect_time' =>$time
                ],
            'data' => $PDF_public_url
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }


    public function v2_stu_profile(Request $request)
    {
        $errors = [];
        $time_start = $this->microtime_float();
        if (! $request->input('username')){
            $errors[] = 'username is not provided.';
        }
        if (! $request->input('password')){
            $errors[] = 'password is not provided.';
        }
        if (count($errors)) {
            return response()->json([
                'meta' =>
                    [
                        'code' => 400,
                        'message' => 'Bad Request',
                        'error' => $errors
                    ]
            ], 400);
        }

        // If user's input has Arabic/Persian numbers, we change it to standard english numbers
        $persian_numbers = [
            '۰' => '0', '٠' => '0',
            '۱' => '1', '١' => '1',
            '۲' => '2', '٢' => '2',
            '۳' => '3', '٣' => '3',
            '۴' => '4', '٤' => '4',
            '۵' => '5', '٥' => '5',
            '۶' => '6', '٦' => '6',
            '۷' => '7', '٧' => '7',
            '۸' => '8', '٨' => '8',
            '۹' => '9', '٩' => '9',
        ];

        $auth = http_build_query([
            'StID' => strtr($request->input('username'), $persian_numbers),
            'UserPassword' => strtr($request->input('password'), $persian_numbers)
        ]);

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, 'http://stu.sadjad.ac.ir/Interim.php');
        curl_setopt($ch,CURLOPT_POST,2);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $auth);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_COOKIESESSION, 1);
        curl_setopt($ch, CURLOPT_COOKIEFILE, '-');
        curl_setopt($ch, CURLOPT_COOKIEJAR, '-');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.84 Safari/537.36';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_exec($ch);
        curl_setopt($ch,CURLOPT_URL, 'http://stu.sadjad.ac.ir/strcss/ShowStFileNo.php');
        $result = curl_exec($ch);
        $pic_url = 'http://stu.sadjad.ac.ir/strcss/';
        $pic_url .= $this->get_string_between($result, '<img height=150 width=100 src="','">');
        $dom = new \domDocument;
        @$dom->loadHTML($result);
        if (strpos($dom->textContent, ' درخواستبنا به دلایل امنیتی ادامه استفاده شما از سیستم منوط به ورود مجدد به سیستم استلطفا برای ورود مجدد ب')){
            $time_end = $this->microtime_float();
            $time = $time_end - $time_start;

            return response()->json([
                'meta' =>
                    [
                        'code' => 403,
                        'message' => 'Forbidden',
                        'connect_time' => $time
                    ],
            ], 403);
        }
        $dom->preserveWhiteSpace = false;
        $tables = $dom->getElementsByTagName('table');
        $rows = $tables->item(0)->getElementsByTagName('tr');
        $row = [];
        foreach ($rows as $r) {
            $tds = $r->getElementsByTagName('td');
            foreach ($tds as $td) {
                $row[] = $td->textContent;
            }
        }
        $nice = [
            'name' => $row[3],
            'degree' => $row[5],
            'college' => $row[7],
            'last_semester_score' => (float)$row[13],
            'education_status' => $row[17]
        ];

        $rows = $tables->item(1)->getElementsByTagName('tr');
        $row = [];
        foreach ($rows as $r) {
            $tds = $r->getElementsByTagName('td');
            foreach ($tds as $td) {
                $row[] = $td->textContent;
            }
        }
        $nice['name_in_English'] = $row[4];
        $nice['lastname_in_English'] = $row[6];
        $nice['ID_card_number'] = $row[11];
        $nice['phone_number'] = $row[21];
        $nice['start_education_year'] = (int)$row[25];
        $nice['address'] = $row[49];

        $file = app()->basePath('public/static/') . sha1($nice['ID_card_number']) . '.jpg';
        if (
            ( file_exists($file) && time() > filemtime ($file) + 31 * 24 * 60 * 60 ) ||
            ! file_exists($file)
        ) {
            @unlink($file);
            $fp = fopen ($file, 'w+');
            curl_setopt($ch,CURLOPT_URL, $pic_url);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_VERBOSE, 0);
            curl_exec($ch);

            curl_close($ch);
            fclose($fp);
            $nice['profile_picture'] = [
                'public_url' => app('url')->asset('static/' . sha1($nice['ID_card_number']) . '.jpg'),
                'cache' => 'MISS',
                'cache_expires_at' => filemtime ($file) + 31 * 24 * 60 * 60
            ];
        } else {
            $nice['profile_picture'] = [
                'public_url' => app('url')->asset('static/' . sha1($nice['ID_card_number']) . '.jpg'),
                'cache' => 'HIT',
                'cache_expires_at' => filemtime ($file) + 31 * 24 * 60 * 60
            ];
        }

        $time_end = $this->microtime_float();
        $time = $time_end - $time_start;

        return response()->json([
            'meta' =>
                [
                    'code' => 200,
                    'message' => 'OK',
                    'connect_time' =>$time
                ],
            'data' => $nice
        ], 200, [], JSON_UNESCAPED_UNICODE);

    }


    public function library(Request $request)
    {
        $errors = [];
        $time_start = $this->microtime_float();
        if (! $request->input('username')){
            $errors[] = 'username is not provided.';
        }
        if (! $request->input('password')){
            $errors[] = 'password is not provided.';
        }
        if (count($errors)) {
            return response()->json([
                'meta' =>
                    [
                        'code' => 400,
                        'message' => 'Bad Request',
                        'error' => $errors
                    ]
            ], 400);
        }

        // If user's input has Arabic/Persian numbers, we change it to standard english numbers
        $persian_numbers = [
            '۰' => '0', '٠' => '0',
            '۱' => '1', '١' => '1',
            '۲' => '2', '٢' => '2',
            '۳' => '3', '٣' => '3',
            '۴' => '4', '٤' => '4',
            '۵' => '5', '٥' => '5',
            '۶' => '6', '٦' => '6',
            '۷' => '7', '٧' => '7',
            '۸' => '8', '٨' => '8',
            '۹' => '9', '٩' => '9',
        ];

        $auth = http_build_query([
            'login' => strtr($request->input('username'), $persian_numbers),
            'password' => strtr($request->input('password'), $persian_numbers)
        ]);

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, 'http://library.sadjad.ac.ir/opac/borrower.php');
        curl_setopt($ch,CURLOPT_POST,2);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $auth . '&ok=تایید');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_COOKIESESSION, 1);
        curl_setopt($ch, CURLOPT_COOKIEFILE, '-');
        curl_setopt($ch, CURLOPT_COOKIEJAR, '-');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.84 Safari/537.36';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if (strpos(curl_exec($ch), 'کد کاربری یا کلمه عبور اشتباه است') !== false) {
            $time_end = $this->microtime_float();
            $time = $time_end - $time_start;

            return response()->json([
                'meta' =>
                    [
                        'code' => 403,
                        'message' => 'Forbidden',
                        'connect_time' => $time
                    ],
            ], 403);
        }
        curl_setopt($ch,CURLOPT_URL,'http://library.sadjad.ac.ir/opac/borrower.php?tab=loan&lvl=all');
        $result = curl_exec($ch);
        $dom = new \domDocument;
        @$dom->loadHTML($result);
        $dom->preserveWhiteSpace = false;
        $tables = $dom->getElementsByTagName('table');
        if ( $tables->length ) {
            $rows = $tables->item(0)->getElementsByTagName('tr');
            $raw = [];
            $results = [];
            foreach ($rows as $row) {
                $tds = $row->getElementsByTagName('td');
                foreach ($tds as $td) {
                    $raw[] = $td->textContent;
                }
            }
            $i = 0;

            $date = new jDateTime(true, true, 'Asia/Tehran');
            while ($i < count($raw)) {
                $borrow_date = explode('/', $this->date_convert($raw[$i + 4]));
                $borrow_date = $date->mktime(0, 0, 0, $borrow_date[1], $borrow_date[2], $borrow_date[0]);

                $borrow_date_ends = explode('/', $this->date_convert($raw[$i + 5]));
                $borrow_date_ends = $date->mktime(0, 0, 0, $borrow_date_ends[1], $borrow_date_ends[2], $borrow_date_ends[0]);
                $results [] =
                    [
                        'title' => $raw[$i + 1],
                        'author' => $raw[$i + 2],
                        'borrow_date' => [
                            'timezone' => 'Asia/Tehran',
                            'date' => (int)$borrow_date,
                            'date_formatted' => date("Y-m-d"),
                            'persian_date' => $date->date("Y-m-d", $borrow_date, false),
                            'persian_date_formatted' => $date->date("l، j F Y", $borrow_date),
                        ],
                        'borrow_date_ends' => [
                            'timezone' => 'Asia/Tehran',
                            'date' => (int)$borrow_date_ends,
                            'date_formatted' => date("Y-m-d"),
                            'persian_date' => $date->date("Y-m-d", $borrow_date_ends, false),
                            'persian_date_formatted' => $date->date("l، j F Y", $borrow_date_ends),
                        ],
                        'times_of_borrow' => $raw[$i + 6] + 0,
                        'times_of_borrow_limit' => $raw[$i + 7] + 0
                    ];
                $i += 9;
            }
        } else {
            $results = null;
        }
        $time_end = $this->microtime_float();
        $time = $time_end - $time_start;

        return response()->json([
            'meta' =>
                [
                    'code' => 200,
                    'message' => 'OK',
                    'connect_time' =>$time
                ],
            'data' => $results
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function exams(Request $request)
    {
        $errors = [];
        $time_start = $this->microtime_float();
        if (! $request->input('username')){
            $errors[] = 'username is not provided.';
        }
        if (! $request->input('password')){
            $errors[] = 'password is not provided.';
        }
        if (count($errors)) {
            return response()->json([
                'meta' =>
                    [
                        'code' => 400,
                        'message' => 'Bad Request',
                        'error' => $errors
                    ]
            ], 400);
        }

        // If user's input has Arabic/Persian numbers, we change it to standard english numbers
        $persian_numbers = [
            '۰' => '0', '٠' => '0',
            '۱' => '1', '١' => '1',
            '۲' => '2', '٢' => '2',
            '۳' => '3', '٣' => '3',
            '۴' => '4', '٤' => '4',
            '۵' => '5', '٥' => '5',
            '۶' => '6', '٦' => '6',
            '۷' => '7', '٧' => '7',
            '۸' => '8', '٨' => '8',
            '۹' => '9', '٩' => '9',
        ];

        $auth = http_build_query([
            'StID' => strtr($request->input('username'), $persian_numbers),
            'UserPassword' => strtr($request->input('password'), $persian_numbers)
        ]);

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,'http://stu.sadjad.ac.ir/Interim.php');
        curl_setopt($ch,CURLOPT_POST,2);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $auth);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_COOKIESESSION, 1);
        curl_setopt($ch, CURLOPT_COOKIEFILE, '-');
        curl_setopt($ch, CURLOPT_COOKIEJAR, '-');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.84 Safari/537.36';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_exec($ch);
        curl_setopt($ch,CURLOPT_URL,'http://stu.sadjad.ac.ir/strcss/ShowStExamDays.php');
        curl_setopt($ch,CURLOPT_POSTFIELDS,'EduYear=1395&semester=1&show_exam_dates=نمایش');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $result = curl_exec($ch);
        if (strlen($result) == 928) {
            $time_end = $this->microtime_float();
            $time = $time_end - $time_start;

            return response()->json([
                'meta' =>
                    [
                        'code' => 403,
                        'message' => 'Forbidden',
                        'connect_time' => $time
                    ],
            ], 403);
        }
        $dom = new \domDocument;
        @$dom->loadHTML($result);
        $dom->preserveWhiteSpace = false;
        $tables = $dom->getElementsByTagName('table');
        $rows = $tables->item(0)->getElementsByTagName('tr');
        $raw = [];
        $result = [];
        foreach ($rows as $row) {
            $tds = $row->getElementsByTagName('td');
            foreach ($tds as $td) {
                $raw[] = $td->textContent;

            }
        }
        $i =2;

        while($i<count($raw))
        {
            $result [] =
                [
                    'course'=> $raw[$i],
                    'teacher'=> $raw[$i+=1],
                    'day' => $raw[$i+=1]
                ];
            $i+=3;
        }

        $time_end = $this->microtime_float();
        $time = $time_end - $time_start;

        return response()->json([
            'meta' =>
                [
                    'code' => 200,
                    'message' => 'OK',
                    'connect_time' =>$time
                ],
            'data' => $result
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function self_service_menu(Request $request)
    {
        $errors = [];
        $time_start = $this->microtime_float();
        if (! $request->input('username')){
            $errors[] = 'username is not provided.';
        }
        if (! $request->input('password')){
            $errors[] = 'password is not provided.';
        }
        if (count($errors)) {
            return response()->json([
                'meta' =>
                    [
                        'code' => 400,
                        'message' => 'Bad Request',
                        'error' => $errors
                    ]
            ], 400);
        }

        // If user's input has Arabic/Persian numbers, we change it to standard english numbers
        $persian_numbers = [
            '۰' => '0', '٠' => '0',
            '۱' => '1', '١' => '1',
            '۲' => '2', '٢' => '2',
            '۳' => '3', '٣' => '3',
            '۴' => '4', '٤' => '4',
            '۵' => '5', '٥' => '5',
            '۶' => '6', '٦' => '6',
            '۷' => '7', '٧' => '7',
            '۸' => '8', '٨' => '8',
            '۹' => '9', '٩' => '9',
        ];

        $auth = http_build_query([
            'txtusername' => strtr($request->input('username'), $persian_numbers),
            'txtpassword' => strtr($request->input('password'), $persian_numbers)
        ]);

        $post_fields = '__LASTFOCUS=&__EVENTTARGET=&__EVENTARGUMENT=&__VIEWSTATE=%2FwEPDwUKMTQyMDUyNzk5NQ9kFgICAw9kFg4CAw8PFgIeBFRleHQFSdiz24zYs9iq2YUg2KfYqtmI2YXYp9iz24zZiNmGINiq2LrYsNuM2Ycg2K%2FYp9mG2LTar9in2Ycg2LPYrNin2K8g2YXYtNmH2K9kZAIPDw8WBh8ABVHYtNmF2Kcg2KjYpyDZhdmI2YHZgtuM2Kog2KfYsiDZhdit24zYtyDaqdin2LHYqNix24wg2K7ZiNivINiu2KfYsdisINi02K%2FZhyDYp9uM2K8eCENzc0NsYXNzBQdtZXNzYWdlHgRfIVNCAgJkZAIRDw8WAh4HVmlzaWJsZWhkZAITDxYCHwNoZAJLDw8WAh8ABbMC2K%2FYp9mG2LTYrNmI2YrYp9mGINmF2K3Yqtix2YU6INmG2KfZhSDaqdin2LHYqNix2Yog2LTZhdin2LHZhyDYr9in2YbYtNis2YjZitmKINmI2qnZhNmF2Ycg2LnYqNmI2LEg2KjYtdmI2LHYqiDZvtmK2LQg2YHYsdi2IDEg2YXZiiDYqNin2LTYry4g2KjZhyDYr9mE2YrZhCDYrNmE2Yjar9mK2LHZiiDYp9iyINmH2LHar9mI2YbZhyDYp9mF2qnYp9mGINin2LPYqtmB2KfYr9mHINi62YrYsSDZhdis2KfYsiDZvtizINin2LIg2KfZiNmE2YrZhiDZiNix2YjYryAg2KfZgtiv2KfZhSDYqNmHINiq2LrZitmK2LEg2KLZhiDZhtmF2KfZitmK2K8gLmRkAk0PDxYCHwAFggEg2qnZhNmK2Ycg2K3ZgtmI2YIg2KfZitmGINin2KvYsSDYt9io2YIg2YLZiNin2YbZitmGINmG2LHZhSDYp9mB2LLYp9ix2Yog2YXYqti52YTZgiDYqNmHINi02LHaqdiqINis2YfYp9mGINqv2LPYqtixINmF2YrYqNin2LTYry4gZGQCTw8PFgIfAAUPVmVyc2lvbiA6IDcuMTQ5ZGQYAgUeX19Db250cm9sc1JlcXVpcmVQb3N0QmFja0tleV9fFg8FBGltZzUFBGltZzQFBGltZzMFBGltZzIFBGltZzEFBWltZzEwBQRpbWc5BQRpbWc4BQRpbWc3BQRpbWc2BQVpbWcxNQUFaW1nMTQFBWltZzEzBQVpbWcxMgUFaW1nMTEFD0NhcHRjaGFDb250cm9sMQ8FJGU3ZTgyODNjLTc4NDgtNGRiMC05ODQ0LWIzOTg5OTlmMDhjNmTM2p8PhsaroGXD3Ekp9e6wrW6oQw%3D%3D&__EVENTVALIDATION=%2FwEWEwKruNLIDQKO1e7aAwKO1YK2DAKO1ZaRBQKO1arsDQKO1b7HBgL0yJmSAgKO1d6gDAKO1fL7BAKO1cakAgKO1dr%2FCgKl1bKdCAK1qbT2CQKC3IfLCQLxyJmSAgLwyJmSAgL3yJmSAgL2yJmSAgL1yJmSAgOLjf%2BoLRxR%2FQUPB4ghsTEHoyG7&'.$auth.'&btnlogin=%D9%88%D8%B1%D9%88%D8%AF';
        $ch = curl_init("http://178.236.33.131/login.aspx");
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.8) Gecko/2009032609 Firefox/3.0.8');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_COOKIESESSION, 1);
        curl_setopt($ch, CURLOPT_COOKIEFILE, '-');
        curl_setopt($ch, CURLOPT_COOKIEJAR, '-');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        curl_exec($ch);
        curl_setopt($ch, CURLOPT_URL, "http://178.236.33.131/Reserve.aspx");
        curl_setopt($ch, CURLOPT_POST, 0);
        $x = curl_exec($ch);

        if (strpos($x, 'نام کاربری و کلمه عبور خود را وارد نمائید') !== false) {
            $time_end = $this->microtime_float();
            $time = $time_end - $time_start;

            return response()->json([
                'meta' =>
                    [
                        'code' => 403,
                        'message' => 'Forbidden',
                        'connect_time' => $time
                    ],
            ], 403);
        }

        $final_days[] = [
            'name_of_week' => 'شنبه',
            'day_of_week' => 0,
            'menu' => $this->get_string_between($x, "<TD id=\"lblghaza2\" class=\"ghaza\" colspan=\"3\"><span id=\"lblsat_ghazaN1\">","</span>") == ' ' ? null : $this->get_string_between($x, "<TD id=\"lblghaza2\" class=\"ghaza\" colspan=\"3\"><span id=\"lblsat_ghazaN1\">","</span>")
        ];
        $final_days[] = [
            'name_of_week' => 'یکشنبه',
            'day_of_week' => 1,
            'menu' => $this->get_string_between($x, "<TD id=\"lblghaza5\" class=\"ghaza\" colspan=\"3\"><span id=\"lblSun_ghazaN2\">","</span>") == ' ' ? null : $this->get_string_between($x, "<TD id=\"lblghaza5\" class=\"ghaza\" colspan=\"3\"><span id=\"lblSun_ghazaN2\">","</span>")
        ];
        $final_days[] = [
            'name_of_week' => 'دوشنبه',
            'day_of_week' => 2,
            'menu' => $this->get_string_between($x, "<TD id=\"lblghaza8\" class=\"ghaza\" colspan=\"3\"><span id=\"lblMon_ghazaN3\">","</span>") == ' ' ? null : $this->get_string_between($x, "<TD id=\"lblghaza8\" class=\"ghaza\" colspan=\"3\"><span id=\"lblMon_ghazaN3\">","</span>")
        ];
        $final_days[] = [
            'name_of_week' => 'سه‌شنبه',
            'day_of_week' => 3,
            'menu' => $this->get_string_between($x, "<TD id=\"lblghaza11\" class=\"ghaza\" colspan=\"3\"><span id=\"lblthr_ghazaN4\">","</span>") == ' ' ? null : $this->get_string_between($x, "<TD id=\"lblghaza11\" class=\"ghaza\" colspan=\"3\"><span id=\"lblthr_ghazaN4\">","</span>")
        ];
        $final_days[] = [
            'name_of_week' => 'چهارشنبه',
            'day_of_week' => 4,
            'menu' => $this->get_string_between($x, "<TD id=\"lblghaza14\" class=\"ghaza\" colspan=\"3\"><span id=\"lblWed_ghazaN5\">","</span>") == ' ' ? null : $this->get_string_between($x, "<TD id=\"lblghaza14\" class=\"ghaza\" colspan=\"3\"><span id=\"lblWed_ghazaN5\">","</span>","</span>")
        ];
        $final_days[] = [
            'name_of_week' => 'پنج‌شنبه',
            'day_of_week' => 5,
            'menu' => $this->get_string_between($x, "<TD id=\"lblghaza17\" class=\"ghaza\" colspan=\"3\"><span id=\"lblTur_ghazaN6\">","</span>") == ' ' ? null : $this->get_string_between($x, "<TD id=\"lblghaza17\" class=\"ghaza\" colspan=\"3\"><span id=\"lblTur_ghazaN6\">","</span>")
        ];

        $time_end = $this->microtime_float();
        $time = $time_end - $time_start;

        return response()->json([
            'meta' =>
                [
                    'code' => 200,
                    'message' => 'OK',
                    'connect_time' =>$time
                ],
            'data' => $final_days
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }


    public function self_service_credits(Request $request)
    {
        $errors = [];
        $time_start = $this->microtime_float();
        if (! $request->input('username')){
            $errors[] = 'username is not provided.';
        }
        if (! $request->input('password')){
            $errors[] = 'password is not provided.';
        }
        if (count($errors)) {
            return response()->json([
                'meta' =>
                    [
                        'code' => 400,
                        'message' => 'Bad Request',
                        'error' => $errors
                    ]
            ], 400);
        }

        // If user's input has Arabic/Persian numbers, we change it to standard english numbers
        $persian_numbers = [
            '۰' => '0', '٠' => '0',
            '۱' => '1', '١' => '1',
            '۲' => '2', '٢' => '2',
            '۳' => '3', '٣' => '3',
            '۴' => '4', '٤' => '4',
            '۵' => '5', '٥' => '5',
            '۶' => '6', '٦' => '6',
            '۷' => '7', '٧' => '7',
            '۸' => '8', '٨' => '8',
            '۹' => '9', '٩' => '9',
        ];

        $auth = http_build_query([
            'txtusername' => strtr($request->input('username'), $persian_numbers),
            'txtpassword' => strtr($request->input('password'), $persian_numbers)
        ]);
        $post_fields = '__VIEWSTATE=%2FwEPDwUKMTQyMDUyNzk5NQ9kFgICAw9kFg4CAw8PFgIeBFRleHQFSdiz24zYs9iq2YUg2KfYqtmI2YXYp9iz24zZiNmGINiq2LrYsNuM2Ycg2K%2FYp9mG2LTar9in2Ycg2LPYrNin2K8g2YXYtNmH2K9kZAIPDw8WBh8ABVHYtNmF2Kcg2KjYpyDZhdmI2YHZgtuM2Kog2KfYsiDZhdit24zYtyDaqdin2LHYqNix24wg2K7ZiNivINiu2KfYsdisINi02K%2FZhyDYp9uM2K8eCENzc0NsYXNzBQdtZXNzYWdlHgRfIVNCAgJkZAIRDw8WAh4HVmlzaWJsZWhkZAITDxYCHwNoZAJLDw8WAh8ABbMC2K%2FYp9mG2LTYrNmI2YrYp9mGINmF2K3Yqtix2YU6INmG2KfZhSDaqdin2LHYqNix2Yog2LTZhdin2LHZhyDYr9in2YbYtNis2YjZitmKINmI2qnZhNmF2Ycg2LnYqNmI2LEg2KjYtdmI2LHYqiDZvtmK2LQg2YHYsdi2IDEg2YXZiiDYqNin2LTYry4g2KjZhyDYr9mE2YrZhCDYrNmE2Yjar9mK2LHZiiDYp9iyINmH2LHar9mI2YbZhyDYp9mF2qnYp9mGINin2LPYqtmB2KfYr9mHINi62YrYsSDZhdis2KfYsiDZvtizINin2LIg2KfZiNmE2YrZhiDZiNix2YjYryAg2KfZgtiv2KfZhSDYqNmHINiq2LrZitmK2LEg2KLZhiDZhtmF2KfZitmK2K8gLmRkAk0PDxYCHwAFggEg2qnZhNmK2Ycg2K3ZgtmI2YIg2KfZitmGINin2KvYsSDYt9io2YIg2YLZiNin2YbZitmGINmG2LHZhSDYp9mB2LLYp9ix2Yog2YXYqti52YTZgiDYqNmHINi02LHaqdiqINis2YfYp9mGINqv2LPYqtixINmF2YrYqNin2LTYry4gZGQCTw8PFgIfAAUPVmVyc2lvbiA6IDcuMTQ5ZGQYAgUeX19Db250cm9sc1JlcXVpcmVQb3N0QmFja0tleV9fFg8FBGltZzUFBGltZzQFBGltZzMFBGltZzIFBGltZzEFBWltZzEwBQRpbWc5BQRpbWc4BQRpbWc3BQRpbWc2BQVpbWcxNQUFaW1nMTQFBWltZzEzBQVpbWcxMgUFaW1nMTEFD0NhcHRjaGFDb250cm9sMQ8FJDZiOTI5MDc4LTU4MTUtNDg2Zi1iMTAyLThjMTcyNmU4NzVkNGSqYKvKyNQqg1XBXpeaFbZbJH80HQ%3D%3D&__EVENTVALIDATION=%2FwEWEwKj3eyGAwKO1e7aAwKO1YK2DAKO1ZaRBQKO1arsDQKO1b7HBgL0yJmSAgKO1d6gDAKO1fL7BAKO1cakAgKO1dr%2FCgKl1bKdCAK1qbT2CQKC3IfLCQLxyJmSAgLwyJmSAgL3yJmSAgL2yJmSAgL1yJmSAkLP5t7aQgYxOljjcSQUv1S9Yjgc&'.$auth.'&btnlogin=%D9%88%D8%B1%D9%88%D8%AF';
        $ch = curl_init("http://178.236.33.131/login.aspx");
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.8) Gecko/2009032609 Firefox/3.0.8');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_COOKIESESSION, 1);
        curl_setopt($ch, CURLOPT_COOKIEFILE, '-');
        curl_setopt($ch, CURLOPT_COOKIEJAR, '-');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        $x = curl_exec($ch);
        curl_setopt($ch, CURLOPT_URL, "http://178.236.33.131/Reserve.aspx");
        curl_setopt($ch, CURLOPT_POST, 0);
        $x = curl_exec($ch);

        $etebar = $this->get_string_between($x, "<span id=\"lbEtebar\">","</span>");
        $etebar = str_replace(',', '', $etebar);
        $time_end = $this->microtime_float();
        $time = $time_end - $time_start;

        if (strpos($x, 'نام کاربری و کلمه عبور خود را وارد نمائید') !== false) {
            $time_end = $this->microtime_float();
            $time = $time_end - $time_start;

            return response()->json([
                'meta' =>
                    [
                        'code' => 403,
                        'message' => 'Forbidden',
                        'connect_time' => $time
                    ],
            ], 403);
        }

        return response()->json([
            'meta' =>
                [
                    'code' => 200,
                    'message' => 'OK',
                    'connect_time' =>$time
                ],
            'data' => [
                'remaining_credits' => $etebar + 0,
                'remaining_credits_formatted' => $etebar + 0 . ' Rials',
            ]
        ]);

    }


    public function internet_credit(Request $request)
    {
        $errors = [];
        $time_start = $this->microtime_float();
        if (! $request->input('username')){
            $errors[] = 'username is not provided.';
        }
        if (! $request->input('password')){
            $errors[] = 'password is not provided.';
        }
        if (count($errors)) {
            return response()->json([
                'meta' =>
                    [
                        'code' => 400,
                        'message' => 'Bad Request',
                        'error' => $errors
                    ]
            ], 400);
        }

        // If user's input has Arabic/Persian numbers, we change it to standard english numbers
        $persian_numbers = [
            '۰' => '0', '٠' => '0',
            '۱' => '1', '١' => '1',
            '۲' => '2', '٢' => '2',
            '۳' => '3', '٣' => '3',
            '۴' => '4', '٤' => '4',
            '۵' => '5', '٥' => '5',
            '۶' => '6', '٦' => '6',
            '۷' => '7', '٧' => '7',
            '۸' => '8', '٨' => '8',
            '۹' => '9', '٩' => '9',
        ];

        $auth = http_build_query([
            'normal_username' => strtr($request->input('username'), $persian_numbers),
            'normal_password' => strtr($request->input('password'), $persian_numbers)
        ]);

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,'http://178.236.34.178/IBSng/user/');
        curl_setopt($ch,CURLOPT_POST,2);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$auth);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_COOKIESESSION, 1);
        curl_setopt($ch, CURLOPT_COOKIEFILE, '-');
        curl_setopt($ch, CURLOPT_COOKIEJAR, '-');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $headers = array();
        $headers[] = 'User-Agent:Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_exec($ch);
        curl_setopt($ch,CURLOPT_URL,'http://178.236.34.178/IBSng/user/home.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $result = curl_exec($ch);

        $cr = $this->get_string_between($result, '<td class="Form_Content_Row_Left_2Col_light"> مقداراعتبار فعلی :</td>'," UNITS</td>");
        $cr = str_replace(',','', $cr);
        $cr = str_replace('<td class="Form_Content_Row_Right_2Col_light">','', $cr);
        $v = trim($cr);

        $time_end = $this->microtime_float();
        $time = $time_end - $time_start;

        if ($v == '') {
            return response()->json([
                'meta' =>
                    [
                        'code' => 403,
                        'message' => 'Forbidden',
                        'connect_time' => $time
                    ],
            ], 403);
        }

        return response()->json([
            'meta' =>
                [
                    'code' => 200,
                    'message' => 'OK',
                    'connect_time' =>$time
                ],
            'data' => [
                'remaining_credits' => round($v * 1024 * 1024),
                'remaining_credits_formatted' => $this->formatBytes(round($v * 1024 * 1024)),
            ]
        ]);
    }

    public function stu_class(Request $request)
    {
        $errors = [];
        $time_start = $this->microtime_float();
        if (! $request->input('username')){
            $errors[] = 'username is not provided.';
        }
        if (! $request->input('password')){
            $errors[] = 'password is not provided.';
        }
        if (count($errors)) {
            return response()->json([
                'meta' =>
                    [
                        'code' => 400,
                        'message' => 'Bad Request',
                        'error' => $errors
                    ]
            ], 400);
        }

        // If user's input has Arabic/Persian numbers, we change it to standard english numbers
        $persian_numbers = [
            '۰' => '0', '٠' => '0',
            '۱' => '1', '١' => '1',
            '۲' => '2', '٢' => '2',
            '۳' => '3', '٣' => '3',
            '۴' => '4', '٤' => '4',
            '۵' => '5', '٥' => '5',
            '۶' => '6', '٦' => '6',
            '۷' => '7', '٧' => '7',
            '۸' => '8', '٨' => '8',
            '۹' => '9', '٩' => '9',
        ];

        $auth = http_build_query([
            'StID' => strtr($request->input('username'), $persian_numbers),
            'UserPassword' => strtr($request->input('password'), $persian_numbers)
        ]);

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, 'http://stu.sadjad.ac.ir/Interim.php');
        curl_setopt($ch,CURLOPT_POST, 2);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$auth);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_COOKIESESSION, 1);
        curl_setopt($ch, CURLOPT_COOKIEFILE, '-');
        curl_setopt($ch, CURLOPT_COOKIEJAR, '-');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.84 Safari/537.36';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_exec($ch);
        curl_setopt($ch,CURLOPT_URL, 'http://stu.sadjad.ac.ir/strcss/ShowStSchedule.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $result = curl_exec($ch);
        $dom = new \domDocument;

        @$dom->loadHTML($result);
        if (strpos($dom->textContent, ' درخواستبنا به دلایل امنیتی ادامه استفاده شما از سیستم منوط به ورود مجدد به سیستم استلطفا برای ورود مجدد ب')){
            $time_end = $this->microtime_float();
            $time = $time_end - $time_start;

            return response()->json([
                'meta' =>
                    [
                        'code' => 403,
                        'message' => 'Forbidden',
                        'connect_time' => $time
                    ],
            ], 403);
        }
        $dom->preserveWhiteSpace = false;
        $tables = $dom->getElementsByTagName('table');
        $rows = $tables->item(1)->getElementsByTagName('tr');
        $raw = [];
        $colspan = [];
        foreach ($rows as $row) {
            $tds = $row->getElementsByTagName('td');
            foreach ($tds as $td) {
                $raw[] = $td->textContent;
                $colspan[] = $td->getattribute('colspan');
            }
        }
        $day = [
            ['odd' => [], 'even' => []],
            ['odd' => [], 'even' => []],
            ['odd' => [], 'even' => []],
            ['odd' => [], 'even' => []],
            ['odd' => [], 'even' => []],
            ['odd' => [], 'even' => []],
            ['odd' => [], 'even' => []],
            ['odd' => [], 'even' => []],
            ['odd' => [], 'even' => []]

        ];
        $day_iterate = -1;
        $i = 0;
        $hour = 0;
        $time = 5;
        while(1){
            if ($raw[$i] == 'جمعه') {
                break;
            }
            if ($raw[$i] == 'شنبه' || $raw[$i] == 'یکشنبه' || $raw[$i] == 'دوشنبه' || $raw[$i] == 'سه شنبه' || $raw[$i] == 'چهارشنبه' || $raw[$i] == 'پنجشنبه'){
                $hour = 0;
                $day_iterate++;
                $time = 6;
            } elseif ($raw[$i] == ' ') {
                $time++;
                $hour++;
            } else {
                $cl = str_replace('(0)','', $raw[$i]);
                $cl = str_replace('*','', $cl);
                $cl = str_replace('.00','', $cl);
                $cl = str_replace(',',' - ', $cl);
                $cl = str_replace('كلاس','', $cl);
                $cl = str_replace('  ',' ', $cl);
                $cl = str_replace('-','', $cl);

                if (strpos($cl, 'زوج')) {
                    array_push($day[$day_iterate]['even'], [
                        'time' => $time,
                        'subject' => str_replace('زوج','', $cl)
                    ]);
                } elseif (strpos($cl, 'فرد')) {
                    array_push($day[$day_iterate]['odd'], [
                        'time' => $time,
                        'subject' => str_replace('فرد','', $cl)
                    ]);
                } else {
                    array_push($day[$day_iterate]['even'], [
                        'time' => $time,
                        'subject' => str_replace('زوج','', $cl)
                    ]);
                    array_push($day[$day_iterate]['odd'], [
                        'time' => $time,
                        'subject' => str_replace('فرد','', $cl)
                    ]);
                }
//                if (strpos($raw[$i], 'پروژه') !== false){
//                    $time += 1;
//                    $hour += 1;
//                } else {
//                    $time += 2;
//                    $hour += 2;
//                } // poor coding abilities!

                $time += $colspan[$i];
                $hour += $colspan[$i];

            }
            if ($hour >= 15) {
                $hour = 0;
                $time = 6;
            }

            $i++;
        }


        $final_days[] = [
            'name_of_week' => 'شنبه',
            'day_of_week' => 0,
            'classes' => $day[0]
        ];
        $final_days[] = [
            'name_of_week' => 'یکشنبه',
            'day_of_week' => 1,
            'classes' => $day[1]
        ];
        $final_days[] = [
            'name_of_week' => 'دوشنبه',
            'day_of_week' => 2,
            'classes' => $day[2]
        ];
        $final_days[] = [
            'name_of_week' => 'سه‌شنبه',
            'day_of_week' => 3,
            'classes' => $day[3]
        ];
        $final_days[] = [
            'name_of_week' => 'چهارشنبه',
            'day_of_week' => 4,
            'classes' => $day[4]
        ];
        $final_days[] = [
            'name_of_week' => 'پنج‌شنبه',
            'day_of_week' => 5,
            'classes' => $day[5]
        ];

        $time_end = $this->microtime_float();
        $time = $time_end - $time_start;

        return response()->json([
            'meta' =>
                [
                    'code' => 200,
                    'message' => 'OK',
                    'connect_time' =>$time
                ],
            'data' => $final_days
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
