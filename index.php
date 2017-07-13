<?php
/*
 *
 * - PopojiCMS Front End File
 *
 * - File : post.php
 * - Version : 1.3
 * - Author : Jenuar Dalapang
 * - License : MIT License
 *
 *
 * Ini adalah file php yang di gunakan untuk menangani proses di bagian depan untuk halaman post.
 * This is a php file for handling front end process for post page.
 *
*/

if (SLUG_PERMALINK == 'pdf') {
        /**
         * Router untuk menampilkan request halaman post.
         *
         * Router for display request in post page.
         *
         * $seotitle = string [a-z0-9_-]
        */
        $router->match('GET|POST', '/pdf/([a-z0-9_-]+)', function($seotitle) use ($core, $templates) {
                $lang = $core->setlang('post', WEB_LANG);
                $post = $core->podb->from('post')
                        ->select(array('post_description.title', 'post_description.content'))
                        ->leftJoin('post_description ON post_description.id_post = post.id_post')
                        ->where('post.seotitle', $seotitle)
                        ->where('post_description.id_language', WEB_LANG_ID)
                        ->where('post.active', 'Y')
                        ->where('post.publishdate < ?', date('Y-m-d H:i:s'))
                        ->limit(1)
                        ->fetch();
                if ($post) {
                        if (!empty($_POST)) {
                                require_once(DIR_INC.'/core/vendor/recaptcha/recaptchalib.php');
                                $secret = $core->posetting[22]['value'];
                                $recaptcha = new PoReCaptcha($secret);
                                if (!empty($_POST["g-recaptcha-response"])) {
                                        $resp = $recaptcha->verifyResponse(
                                                $_SERVER["REMOTE_ADDR"],
                                                $_POST["g-recaptcha-response"]
                                        );
                                        if ($resp != null && $resp->success) {
                                                $core->poval->validation_rules(array(
                                                        'id' => 'required|integer',
                                                        'id_parent' => 'required|integer',
                                                        'name' => 'required|max_len,100|min_len,3',
                                                        'email' => 'required|valid_email',
                                                        'url' => 'max_len,255|valid_url',
                                                        'comment' => 'required|min_len,10',
                                                        'seotitle' => 'required'
                                                ));
                                                $core->poval->filter_rules(array(
                                                        'id' => 'trim|sanitize_numbers',
                                                        'id_parent' => 'trim',
                                                        'name' => 'trim|sanitize_string',
                                                        'email' => 'trim|sanitize_email',
                                                        'url' => 'trim|sanitize_string',
                                                        'comment' => 'trim|sanitize_string|basic_tags',
                                                        'seotitle' => 'trim'
                                                ));
                                                $validated_data = $core->poval->run($_POST);
                                                if ($validated_data === false) {
                                                        $core->poflash->error($lang['front_comment_error_3']);
                                                } else {
                                                        if ($core->posetting[18]['value'] == 'Y') {
                                                                $active = 'Y';
                                                        } else {
                                                                $active = 'N';
                                                        }
                                                        $data = array(
                                                                'id_post' => $_POST['id'],
                                                                'id_parent' => $_POST['id_parent'],
                                                                'name' => $_POST['name'],
                                                                'email' => $_POST['email'],
                                                                'url' => $_POST['url'],
                                                                'comment' => $_POST['comment'],
                                                                'date' => date('Y-m-d'),
                                                                'time' => date('h:i:s'),
                                                                'active' => $active
                                                        );
                                                        $query = $core->podb->insertInto('comment')->values($data);
                                                        $query->execute();
                                                        unset($_POST);
                                                        $core->poflash->success($lang['front_comment_success']);
                                                }
                                        } else {
                                                $core->poflash->error($lang['front_comment_error_2']);
                                        }
                                } else {
                                        $core->poflash->error($lang['front_comment_error_1']);
                                }
                        }
                        $query_hits = $core->podb->update('post')
                                ->set(array('hits' => $post['hits']+1))
                                ->where('id_post', $post['id_post']);
                        $query_hits->execute();
                        $info = array(
                                'page_title' => htmlspecialchars_decode($post['title']),
                                'page_desc' => $core->postring->cuthighlight('post', $post['content'], '150'),
                                'page_key' => $post['tag'],
                                'social_mod' => $lang['front_post_title'],
                                'social_name' => $core->posetting[0]['value'],
                                'social_url' => $core->posetting[1]['value'].'/detailpost/'.$post['seotitle'],
                                'social_title' => htmlspecialchars_decode($post['title']),
                                'social_desc' => $core->postring->cuthighlight('post', $post['content'], '150'),
                                'social_img' => $core->posetting[1]['value'].'/'.DIR_CON.'/uploads/'.$post['picture'],
                                'page' => '1'
                        );
                        $adddata = array_merge($info, $lang);
                        $templates->addData(
                                $adddata
                        );
                        echo $templates->render('detailpost', compact('post','lang'));
                } else {
                        $info = array(
                                'page_title' => $lang['front_post_not_found'],
                                'page_desc' => $core->posetting[2]['value'],
                                'page_key' => $core->posetting[3]['value'],
                                'social_mod' => $lang['front_post_title'],
                                'social_name' => $core->posetting[0]['value'],
                                'social_url' => $core->posetting[1]['value'],
                                'social_title' => $lang['front_post_not_found'],
                                'social_desc' => $core->posetting[2]['value'],
                                'social_img' => $core->posetting[1]['value'].'/'.DIR_INC.'/images/favicon.png'
                        );
                        $adddata = array_merge($info, $lang);
                        $templates->addData(
                                $adddata
                        );
                        echo $templates->render('404', compact('lang'));
                }
        });
} 
