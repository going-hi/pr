<?php
declare(strict_types=1);

/**
 * Сидер: создаёт таблицы (sql/schema_for_hosting.sql) при необходимости и заливает демо-данные:
 * пользователи, рецепты с разными авторами, лайки, избранное, комментарии, демо-сообщения формы «Контакты».
 * Браузер: http://localhost:8080/seed.php
 * CLI:     php seed.php [--reset-admin]
 * Docker:  docker compose exec web php /var/www/html/seed.php
 */

$isCli = PHP_SAPI === 'cli';

if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'Сковородка Судьбы');
}

// ─── Конфигурация и подключение к БД ─────────────────────────────────────────

$config = require __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/schema_install.php';
require_once __DIR__ . '/includes/functions.php';

try {
    $pdo = db_connect($config);
    $pdo->query('SELECT 1');
    $dbOk = true;
    $dbError = '';
} catch (Throwable $ex) {
    $dbOk = false;
    $dbError = $ex->getMessage();
}

// ─── Аргументы ────────────────────────────────────────────────────────────────

if ($isCli) {
    $args = array_slice($argv, 1);
    $resetAdmin = in_array('--reset-admin', $args, true);
    $run = true;
} else {
    $resetAdmin = isset($_POST['reset_admin']);
    $run = isset($_POST['run']);
}

// ─── Данные для засева ────────────────────────────────────────────────────────

$seedPassword = 'password';

$users = [
    ['email' => 'admin@example.com', 'full_name' => 'Администратор', 'role' => 'admin'],
    ['email' => 'cook@example.com',  'full_name' => 'Повар Пётр',    'role' => 'user'],
    ['email' => 'maria@example.com', 'full_name' => 'Мария Соколова', 'role' => 'user'],
    ['email' => 'oleg@example.com',  'full_name' => 'Олег Морозов', 'role' => 'user'],
    ['email' => 'anna@example.com',  'full_name' => 'Анна Воронова', 'role' => 'user'],
    ['email' => 'dmitry@example.com', 'full_name' => 'Дмитрий Козлов', 'role' => 'user'],
    ['email' => 'elena@example.com', 'full_name' => 'Елена Белова', 'role' => 'user'],
];

/** Автор рецепта по slug, если в записи нет ключа author_email (по умолчанию — админ). */
$postAuthorBySlug = [
    'klassicheskij-borsch'     => 'cook@example.com',
    'kurinyj-sup-s-lapshoj'     => 'maria@example.com',
    'salat-olive'               => 'elena@example.com',
    'grecheskij-salat'          => 'maria@example.com',
    'tsezar-s-kuricej'          => 'dmitry@example.com',
    'pelmeni-domashnie'         => 'cook@example.com',
    'kotlety-po-domashnemu'     => 'oleg@example.com',
    'kurinie-bedra-v-duhovke'   => 'dmitry@example.com',
    'oladi-na-kefire'           => 'elena@example.com',
    'jaichnitsa-s-pomidorami'   => 'oleg@example.com',
    'bananovy-pankeiki'         => 'anna@example.com',
    'sharlotka-s-jablokami'     => 'maria@example.com',
    'shokoladnyj-brauni'        => 'anna@example.com',
    'panna-kotta-vanilnaja'     => 'elena@example.com',
];

$posts = [
    // ── Супы ──────────────────────────────────────────────────────────────────
    [
        'title'     => 'Классический борщ',
        'slug'      => 'klassicheskij-borsch',
        'category'  => 'soups',
        'image_path'=> '/assets/img/recipes/borsch.jpg',
        'excerpt'   => 'Насыщенный суп со свёклой и говядиной — идеален для обеда.',
        'body'      => "Ингредиенты:\n- говядина с косточкой — 500 г\n- свёкла — 2 шт.\n- морковь — 1 шт.\n- лук — 1 шт.\n- капуста белокочанная — 300 г\n- томатная паста — 2 ст. л.\n- сметана, чеснок, укроп\n\nПриготовление:\n1. Сварите бульон из говядины (1,5 ч). Мясо выньте, нарежьте.\n2. В отдельной сковороде обжарьте лук и морковь, добавьте тёртую свёклу и томатную пасту, тушите 10 мин.\n3. Бросьте в кипящий бульон капусту, через 10 мин — зажарку и мясо.\n4. Варите ещё 5 мин, посолите, добавьте чеснок.\n5. Подавайте со сметаной и свежим укропом.",
        'status' => 'published',
    ],
    [
        'title'     => 'Куриный суп с лапшой',
        'slug'      => 'kurinyj-sup-s-lapshoj',
        'category'  => 'soups',
        'image_path'=> '/assets/img/recipes/soup-chicken.jpg',
        'excerpt'   => 'Простой и сытный суп на каждый день — бульон получается золотистым.',
        'body'      => "Ингредиенты:\n- куриные бёдра — 500 г\n- морковь — 1 шт.\n- лук — 1 шт.\n- яичная лапша — 100 г\n- соль, перец, лавровый лист, зелень\n\nПриготовление:\n1. Залейте курицу холодной водой (2 л), доведите до кипения, снимите пену.\n2. Добавьте целые морковь и лук, лавровый лист. Варите 40 мин на слабом огне.\n3. Выньте лук, морковь нарежьте кружочками, курицу отделите от костей.\n4. Верните в бульон морковь и курицу, добавьте лапшу. Варите 5–7 мин.\n5. Посолите, поперчите, посыпьте зеленью.",
        'status' => 'published',
    ],
    [
        'title'     => 'Грибной крем-суп',
        'slug'      => 'gribnoj-krem-sup',
        'category'  => 'soups',
        'image_path'=> '/assets/img/recipes/mushroom-soup.jpg',
        'excerpt'   => 'Бархатистый суп-пюре из шампиньонов со сливками.',
        'body'      => "Ингредиенты:\n- шампиньоны — 500 г\n- лук — 1 шт.\n- чеснок — 2 зубчика\n- сливки 20% — 200 мл\n- сливочное масло — 30 г\n- бульон овощной — 500 мл\n- соль, перец, мускатный орех\n\nПриготовление:\n1. На масле обжарьте лук и чеснок до мягкости.\n2. Добавьте нарезанные грибы, жарьте до испарения жидкости (8–10 мин).\n3. Влейте бульон, варите 10 мин.\n4. Пробейте блендером до однородности.\n5. Влейте сливки, прогрейте, не доводя до кипения. Приправьте мускатным орехом.",
        'status' => 'published',
    ],

    // ── Салаты ────────────────────────────────────────────────────────────────
    [
        'title'     => 'Салат «Оливье»',
        'slug'      => 'salat-olive',
        'category'  => 'salads',
        'image_path'=> '/assets/img/recipes/salad.jpg',
        'excerpt'   => 'Классический новогодний салат.',
        'body'      => "Ингредиенты:\n- варёная колбаса — 200 г\n- картофель — 3 шт.\n- морковь — 2 шт.\n- яйца — 3 шт.\n- маринованные огурцы — 3 шт.\n- консервированный горошек — 1 банка\n- майонез, соль\n\nПриготовление:\n1. Отварите картофель, морковь и яйца. Остудите.\n2. Нарежьте всё кубиками одинакового размера.\n3. Добавьте горошек, заправьте майонезом, посолите.\n4. Уберите в холодильник на 30 мин перед подачей.",
        'status' => 'published',
    ],
    [
        'title'     => 'Греческий салат',
        'slug'      => 'grecheskij-salat',
        'category'  => 'salads',
        'image_path'=> '/assets/img/recipes/greek-salad.jpg',
        'excerpt'   => 'Сочный салат из свежих овощей с сыром фета.',
        'body'      => "Ингредиенты:\n- огурцы — 2 шт.\n- помидоры — 3 шт.\n- сладкий перец — 1 шт.\n- красный лук — 0,5 шт.\n- маслины — 80 г\n- сыр фета — 150 г\n- оливковое масло, орегано, соль\n\nПриготовление:\n1. Нарежьте овощи крупными кусками, красный лук — полукольцами.\n2. Добавьте маслины и покрошенную фету.\n3. Полейте оливковым маслом, посыпьте орегано, посолите и аккуратно перемешайте.",
        'status' => 'published',
    ],
    [
        'title'     => 'Цезарь с курицей',
        'slug'      => 'tsezar-s-kuricej',
        'category'  => 'salads',
        'image_path'=> '/assets/img/recipes/caesar.jpg',
        'excerpt'   => 'Хрустящий ромэн, сочная курица и домашние крутоны.',
        'body'      => "Ингредиенты:\n- куриное филе — 300 г\n- салат романо — 1 кочан\n- батон — 3 ломтика\n- пармезан — 50 г\n- майонез — 3 ст. л.\n- чеснок — 1 зубчик\n- лимон, соль, перец\n\nПриготовление:\n1. Нарежьте батон кубиками, обжарьте на масле с чесноком до хруста.\n2. Куриное филе посолите, поперчите, обжарьте 6 мин с каждой стороны. Нарежьте полосками.\n3. Смешайте майонез с лимонным соком и тёртым пармезаном — соус готов.\n4. Разорвите листья романо руками, перемешайте с соусом.\n5. Выложите курицу, крутоны, посыпьте стружкой пармезана.",
        'status' => 'published',
    ],

    // ── Горячее ───────────────────────────────────────────────────────────────
    [
        'title'     => 'Пельмени домашние',
        'slug'      => 'pelmeni-domashnie',
        'category'  => 'hot',
        'image_path'=> '/assets/img/recipes/dumplings.jpg',
        'excerpt'   => 'Пельмени с сочной начинкой из смешанного фарша.',
        'body'      => "Тесто:\n- мука — 500 г\n- яйцо — 1 шт.\n- вода — 200 мл\n- соль — 1 ч. л.\n\nНачинка:\n- свиной фарш — 300 г\n- говяжий фарш — 200 г\n- лук — 1 шт.\n- соль, перец\n\nПриготовление:\n1. Замесите тесто, накройте и оставьте на 30 мин.\n2. Смешайте фарш с тёртым луком, солью и перцем.\n3. Раскатайте тесто тонко, вырежьте кружки стаканом.\n4. Положите начинку, слепите пельмени.\n5. Варите в кипящей подсоленной воде 8–10 мин после всплытия.\n6. Подавайте со сметаной или сливочным маслом.",
        'status' => 'published',
    ],
    [
        'title'     => 'Котлеты по-домашнему',
        'slug'      => 'kotlety-po-domashnemu',
        'category'  => 'hot',
        'image_path'=> '/assets/img/recipes/cutlets.jpg',
        'excerpt'   => 'Сочные котлеты с хрустящей корочкой — по бабушкиному рецепту.',
        'body'      => "Ингредиенты:\n- смешанный фарш — 600 г\n- лук — 1 шт.\n- батон (замоченный в молоке) — 2 ломтика\n- яйцо — 1 шт.\n- чеснок — 2 зубчика\n- соль, перец, панировочные сухари\n\nПриготовление:\n1. Отожмите батон и смешайте с фаршем, тёртым луком, чесноком и яйцом. Посолите.\n2. Сформируйте котлеты, обваляйте в сухарях.\n3. Обжарьте на среднем огне по 4–5 мин с каждой стороны до золотистой корочки.\n4. Доведите до готовности под крышкой ещё 5 мин на слабом огне.",
        'status' => 'published',
    ],
    [
        'title'     => 'Паста карбонара',
        'slug'      => 'pasta-karbonara',
        'category'  => 'hot',
        'image_path'=> '/assets/img/recipes/pasta.jpg',
        'excerpt'   => 'Сливочная паста с беконом без единой капли сливок.',
        'body'      => "Ингредиенты:\n- спагетти — 400 г\n- бекон или панчетта — 150 г\n- яйца — 2 шт. + 2 желтка\n- пармезан — 80 г\n- чеснок — 1 зубчик\n- соль, чёрный перец\n\nПриготовление:\n1. Отварите спагетти в сильно подсоленной воде al dente, 1 стакан воды сохраните.\n2. Обжарьте бекон с чесноком, чеснок выньте.\n3. Смешайте яйца, желтки и тёртый пармезан.\n4. Горячие спагетти добавьте к бекону, снимите с огня.\n5. Влейте яичную смесь, быстро перемешайте, разбавляя pasta water до кремовой консистенции.\n6. Щедро поперчите, подавайте сразу.",
        'status' => 'published',
    ],
    [
        'title'     => 'Куриные бёдра в духовке',
        'slug'      => 'kurinie-bedra-v-duhovke',
        'category'  => 'hot',
        'image_path'=> '/assets/img/recipes/chicken-oven.jpg',
        'excerpt'   => 'Хрустящая корочка, сочное мясо — минимум усилий.',
        'body'      => "Ингредиенты:\n- куриные бёдра — 6 шт.\n- чеснок — 4 зубчика\n- оливковое масло — 3 ст. л.\n- паприка, куркума, розмарин, соль, перец\n\nПриготовление:\n1. Смешайте масло, пропущенный чеснок и все специи.\n2. Натрите бёдра маринадом, оставьте на 30 мин (или ночь в холодильнике).\n3. Выложите в форму кожей вверх.\n4. Запекайте при 200 °C 40–45 мин до золотистой корочки.",
        'status' => 'published',
    ],

    // ── Завтраки и выпечка ────────────────────────────────────────────────────
    [
        'title'     => 'Оладьи на кефире',
        'slug'      => 'oladi-na-kefire',
        'category'  => 'breakfast',
        'image_path'=> '/assets/img/recipes/pancakes.jpg',
        'excerpt'   => 'Пышные оладьи за 20 минут.',
        'body'      => "Ингредиенты:\n- кефир — 400 мл\n- яйца — 2 шт.\n- мука — 250–300 г\n- сахар — 2 ст. л.\n- сода — 0,5 ч. л.\n- соль — щепотка\n\nПриготовление:\n1. Смешайте яйца, кефир, сахар и соль.\n2. Всыпьте муку и соду, замесите тесто — оно должно быть гуще сметаны.\n3. Жарьте на среднем огне с двух сторон до румяной корочки.\n4. Подавайте со сметаной, вареньем или мёдом.",
        'status' => 'published',
    ],
    [
        'title'     => 'Яичница с помидорами и сыром',
        'slug'      => 'jaichnitsa-s-pomidorami',
        'category'  => 'breakfast',
        'image_path'=> '/assets/img/recipes/eggs.jpg',
        'excerpt'   => 'Быстрый завтрак за 10 минут.',
        'body'      => "Ингредиенты:\n- яйца — 3 шт.\n- помидоры — 1 шт.\n- сыр — 50 г\n- сливочное масло — 10 г\n- соль, перец, зелень\n\nПриготовление:\n1. На разогретой сковороде растопите масло.\n2. Выложите нарезанные помидоры, жарьте 2 мин.\n3. Разбейте яйца, посолите и поперчите.\n4. Накройте крышкой на 3–4 мин до желаемой степени готовности.\n5. Посыпьте тёртым сыром и зеленью.",
        'status' => 'published',
    ],
    [
        'title'     => 'Банановые панкейки',
        'slug'      => 'bananovy-pankeiki',
        'category'  => 'breakfast',
        'image_path'=> '/assets/img/recipes/banana-pancakes.jpg',
        'excerpt'   => 'Нежные и натурально сладкие — всего 3 ингредиента.',
        'body'      => "Ингредиенты:\n- бананы спелые — 2 шт.\n- яйца — 2 шт.\n- овсяные хлопья — 4 ст. л. (по желанию)\n\nПриготовление:\n1. Разомните банан вилкой до состояния пюре.\n2. Вбейте яйца и перемешайте. Можно добавить хлопья для плотности.\n3. Жарьте небольшие лепёшки на антипригарной сковороде без масла по 2 мин с каждой стороны.\n4. Подавайте с ягодами или мёдом.",
        'status' => 'published',
    ],
    [
        'title'     => 'Шарлотка с яблоками',
        'slug'      => 'sharlotka-s-jablokami',
        'category'  => 'bakery',
        'image_path'=> '/assets/img/recipes/charlotte.jpg',
        'excerpt'   => 'Воздушный яблочный пирог — классика русской выпечки.',
        'body'      => "Ингредиенты:\n- яблоки — 4 шт.\n- яйца — 4 шт.\n- сахар — 200 г\n- мука — 200 г\n- разрыхлитель — 1 ч. л.\n- ванилин — щепотка\n\nПриготовление:\n1. Яйца взбейте с сахаром и ванилином до пышности (5–7 мин миксером).\n2. Аккуратно введите просеянную муку с разрыхлителем.\n3. Яблоки очистите и нарежьте дольками, выложите в смазанную форму.\n4. Залейте тестом и разровняйте.\n5. Выпекайте при 180 °C 35–40 мин. Готовность проверьте зубочисткой.",
        'status' => 'published',
    ],

    // ── Десерты ───────────────────────────────────────────────────────────────
    [
        'title'     => 'Шоколадный брауни',
        'slug'      => 'shokoladnyj-brauni',
        'category'  => 'desserts',
        'image_path'=> '/assets/img/recipes/brownie.jpg',
        'excerpt'   => 'Плотный, влажный и невозможно шоколадный.',
        'body'      => "Ингредиенты:\n- тёмный шоколад 70% — 200 г\n- сливочное масло — 120 г\n- сахар — 200 г\n- яйца — 3 шт.\n- мука — 80 г\n- какао — 2 ст. л.\n- соль — щепотка\n\nПриготовление:\n1. Растопите шоколад и масло на водяной бане. Дайте немного остыть.\n2. Вмешайте сахар, затем по одному яйца.\n3. Добавьте муку, какао и соль, перемешайте до однородности.\n4. Вылейте в форму 20×20 см, застеленную бумагой.\n5. Выпекайте при 170 °C 20–25 мин. Центр должен чуть дрожать.\n6. Полностью остудите перед нарезкой.",
        'status' => 'published',
    ],
    [
        'title'     => 'Панна-котта ванильная',
        'slug'      => 'panna-kotta-vanilnaja',
        'category'  => 'desserts',
        'image_path'=> '/assets/img/recipes/panna-cotta.jpg',
        'excerpt'   => 'Нежный итальянский десерт из сливок — готовится без выпечки.',
        'body'      => "Ингредиенты:\n- сливки 33% — 500 мл\n- сахар — 4 ст. л.\n- желатин — 10 г\n- ванильный стручок (или экстракт) — 1 шт.\n- ягоды для подачи\n\nПриготовление:\n1. Замочите желатин в 50 мл холодной воды на 10 мин.\n2. Нагрейте сливки с сахаром и ванилью, не доводя до кипения.\n3. Снимите с огня, добавьте набухший желатин, перемешайте до растворения.\n4. Разлейте по формочкам, уберите в холодильник на 4 часа.\n5. Подавайте с ягодным соусом или свежими ягодами.",
        'status' => 'published',
    ],

    // ── Рецепты сообщества (дополнительно) ───────────────────────────────────
    [
        'title'     => 'Сырный суп с брокколи',
        'slug'      => 'syrnyj-sup-s-brokkoli',
        'category'  => 'soups',
        'image_path'=> '/assets/img/recipes/mushroom-soup.jpg',
        'author_email' => 'maria@example.com',
        'excerpt'   => 'Нежный крем-суп: дети едят брокколи без капризов.',
        'body'      => "Ингредиенты:\n- брокколи — 400 г\n- картофель — 2 шт.\n- лук — 1 шт.\n- плавленый сыр «Дружба» — 200 г\n- бульон куриный — 700 мл\n- сливки — 100 мл\n- соль, перец, мускат\n\nПриготовление:\n1. Обжарьте лук, добавьте нарезанный картофель и брокколи.\n2. Влейте бульон, варите 15 мин до мягкости овощей.\n3. Добавьте сыр, размешайте до растворения.\n4. Пробейте блендером, влейте сливки, прогрейте.\n5. Приправьте по вкусу.",
        'status' => 'published',
    ],
    [
        'title'     => 'Ленивые голубцы в духовке',
        'slug'      => 'lenivye-golubtsy-v-duhovke',
        'category'  => 'hot',
        'image_path'=> '/assets/img/recipes/cutlets.jpg',
        'author_email' => 'oleg@example.com',
        'excerpt'   => 'Всё в одной форме — без возни с листьями капусты.',
        'body'      => "Ингредиенты:\n- фарш смешанный — 600 г\n- рис отварной — 200 г\n- капуста тёртая — 300 г\n- морковь, лук — по 1 шт.\n- томатная паста — 2 ст. л.\n- сметана — 3 ст. л.\n- соль, перец\n\nПриготовление:\n1. Смешайте фарш, рис, капусту, тёртые овощи, специи.\n2. Выложите «котлеты» в форму.\n3. Смешайте пасту, сметану и стакан воды — залейте.\n4. Запекайте под фольгой 40 мин при 180 °C, снимите фольгу ещё на 10 мин.",
        'status' => 'published',
    ],
    [
        'title'     => 'Сырники из творога (пышные)',
        'slug'      => 'syrniki-iz-tvoroga-pyshnye',
        'category'  => 'breakfast',
        'image_path'=> '/assets/img/recipes/pancakes.jpg',
        'author_email' => 'anna@example.com',
        'excerpt'   => 'С манкой внутри держат форму и не разваливаются.',
        'body'      => "Ингредиенты:\n- творог 9% — 400 г\n- яйцо — 1 шт.\n- сахар — 3 ст. л.\n- манка — 3 ст. л.\n- мука для обвалки\n- соль — щепотка\n- масло для жарки\n\nПриготовление:\n1. Разомните творог с яйцом и сахаром.\n2. Всыпьте манку, оставьте на 15 мин.\n3. Сформируйте лепёшки, обваляйте в муке.\n4. Жарьте на среднем огне по 3 мин с каждой стороны.\n5. Подавайте со сметаной или вареньем.",
        'status' => 'published',
    ],
    [
        'title'     => 'Лимонный кекс на кефире',
        'slug'      => 'limonnyj-keks-na-kefire',
        'category'  => 'bakery',
        'image_path'=> '/assets/img/recipes/charlotte.jpg',
        'author_email' => 'elena@example.com',
        'excerpt'   => 'Ароматный, с цедрой — к чаю в выходные.',
        'body'      => "Ингредиенты:\n- кефир — 200 мл\n- яйца — 3 шт.\n- сахар — 180 г\n- мука — 220 г\n- разрыхлитель — 1 ч. л.\n- лимон — 1 шт. (цедра + сок)\n- растительное масло — 80 мл\n\nПриготовление:\n1. Взбейте яйца с сахаром, влейте кефир и масло.\n2. Добавьте цедру и ложку лимонного сока.\n3. Введите муку с разрыхлителем.\n4. Выпекайте в смазанной форме 40–45 мин при 170 °C.",
        'status' => 'published',
    ],
    [
        'title'     => 'Гречка с курицей и овощами',
        'slug'      => 'grechka-s-kuricej-i-ovoshhami',
        'category'  => 'hot',
        'image_path'=> '/assets/img/recipes/chicken-oven.jpg',
        'author_email' => 'dmitry@example.com',
        'excerpt'   => 'Одна сковорода — ужин за полчаса.',
        'body'      => "Ингредиенты:\n- куриное филе — 400 г\n- гречка — 1 стакан\n- лук, морковь — по 1 шт.\n- чеснок — 2 зубчика\n- вода — 2 стакана\n- соль, перец, паприка, зелень\n\nПриготовление:\n1. Обжарьте кубики курицы до румянца, выньте.\n2. На том же масле — лук и морковь, добавьте гречку, обжарьте 2 мин.\n3. Верните курицу, влейте воду, чеснок, специи.\n4. Тушите под крышкой 18–20 мин до готовности гречки.\n5. Посыпьте зеленью.",
        'status' => 'published',
    ],
];

/** Лайки: повторный запуск не дублирует (UNIQUE post_id, user_id). */
$seedLikes = [
    ['slug' => 'klassicheskij-borsch', 'email' => 'maria@example.com'],
    ['slug' => 'klassicheskij-borsch', 'email' => 'oleg@example.com'],
    ['slug' => 'klassicheskij-borsch', 'email' => 'anna@example.com'],
    ['slug' => 'pelmeni-domashnie', 'email' => 'maria@example.com'],
    ['slug' => 'pelmeni-domashnie', 'email' => 'dmitry@example.com'],
    ['slug' => 'pelmeni-domashnie', 'email' => 'elena@example.com'],
    ['slug' => 'shokoladnyj-brauni', 'email' => 'cook@example.com'],
    ['slug' => 'shokoladnyj-brauni', 'email' => 'oleg@example.com'],
    ['slug' => 'pasta-karbonara', 'email' => 'maria@example.com'],
    ['slug' => 'pasta-karbonara', 'email' => 'anna@example.com'],
    ['slug' => 'gribnoj-krem-sup', 'email' => 'elena@example.com'],
    ['slug' => 'grecheskij-salat', 'email' => 'dmitry@example.com'],
    ['slug' => 'tsezar-s-kuricej', 'email' => 'anna@example.com'],
    ['slug' => 'kurinyj-sup-s-lapshoj', 'email' => 'oleg@example.com'],
    ['slug' => 'kotlety-po-domashnemu', 'email' => 'maria@example.com'],
    ['slug' => 'bananovy-pankeiki', 'email' => 'elena@example.com'],
    ['slug' => 'sharlotka-s-jablokami', 'email' => 'cook@example.com'],
    ['slug' => 'syrnyj-sup-s-brokkoli', 'email' => 'cook@example.com'],
    ['slug' => 'syrnyj-sup-s-brokkoli', 'email' => 'oleg@example.com'],
    ['slug' => 'syrniki-iz-tvoroga-pyshnye', 'email' => 'maria@example.com'],
    ['slug' => 'syrniki-iz-tvoroga-pyshnye', 'email' => 'dmitry@example.com'],
    ['slug' => 'grechka-s-kuricej-i-ovoshhami', 'email' => 'anna@example.com'],
    ['slug' => 'lenivye-golubtsy-v-duhovke', 'email' => 'maria@example.com'],
];

$seedFavorites = [
    ['slug' => 'klassicheskij-borsch', 'email' => 'maria@example.com'],
    ['slug' => 'pelmeni-domashnie', 'email' => 'oleg@example.com'],
    ['slug' => 'shokoladnyj-brauni', 'email' => 'anna@example.com'],
    ['slug' => 'pasta-karbonara', 'email' => 'dmitry@example.com'],
    ['slug' => 'gribnoj-krem-sup', 'email' => 'elena@example.com'],
    ['slug' => 'bananovy-pankeiki', 'email' => 'anna@example.com'],
    ['slug' => 'syrniki-iz-tvoroga-pyshnye', 'email' => 'elena@example.com'],
    ['slug' => 'limonnyj-keks-na-kefire', 'email' => 'cook@example.com'],
    ['slug' => 'grechka-s-kuricej-i-ovoshhami', 'email' => 'oleg@example.com'],
];

$seedComments = [
    ['slug' => 'klassicheskij-borsch', 'email' => 'maria@example.com', 'body' => 'Готовила по рецепту — борщ получился насыщенным. Добавила щепотку сахара в зажарку, как учила бабушка.'],
    ['slug' => 'klassicheskij-borsch', 'email' => 'oleg@example.com', 'body' => 'Варил в мультиварке на режиме «Тушение» — тоже отлично. На второй день ещё вкуснее.'],
    ['slug' => 'klassicheskij-borsch', 'email' => 'elena@example.com', 'body' => 'Фото бы выложили в конце, а так рецепт понятный, спасибо!'],
    ['slug' => 'pelmeni-domashnie', 'email' => 'dmitry@example.com', 'body' => 'Тесто тонкое получилось. Фарш взял с говядиной побольше — сочнее.'],
    ['slug' => 'pelmeni-domashnie', 'email' => 'anna@example.com', 'body' => 'Лепила вдвоём с сестрой, за вечер 200 штук. Заморозили — супер заготовка.'],
    ['slug' => 'shokoladnyj-brauni', 'email' => 'maria@example.com', 'body' => 'Пекла 22 минуты — центр остался влажным, как надо. Орехи горького шоколада сверху добавила.'],
    ['slug' => 'shokoladnyj-brauni', 'email' => 'cook@example.com', 'body' => 'Профессиональный совет: не пересушите. Если сомневаетесь — лучше достаньте раньше.'],
    ['slug' => 'pasta-karbonara', 'email' => 'oleg@example.com', 'body' => 'Без сливок — реально кремовая. Главное сразу есть, пока горячая.'],
    ['slug' => 'pasta-karbonara', 'email' => 'elena@example.com', 'body' => 'Заменила панчетту на обычный бекон из магазина — тоже зашло.'],
    ['slug' => 'gribnoj-krem-sup', 'email' => 'maria@example.com', 'body' => 'Взяла лесные грибы замороженные — аромат сильнее, чем со шампиньонами.'],
    ['slug' => 'grecheskij-salat', 'email' => 'anna@example.com', 'body' => 'Лёгкий ужин летом. Фету режу крупнее, чтобы чувствовалась.'],
    ['slug' => 'tsezar-s-kuricej', 'email' => 'elena@example.com', 'body' => 'Крутоны делала из багета с чесноком — хрустят идеально.'],
    ['slug' => 'kurinyj-sup-s-lapshoj', 'email' => 'dmitry@example.com', 'body' => 'Дети просят этот суп каждую неделю. Лапшу иногда меняю на вермишель.'],
    ['slug' => 'salat-olive', 'email' => 'oleg@example.com', 'body' => 'Классика. Колбасу взял докторскую — как в детстве.'],
    ['slug' => 'kotlety-po-domashnemu', 'email' => 'maria@example.com', 'body' => 'Сочные, с хрустящей корочкой. Батон обязательно отжать хорошо.'],
    ['slug' => 'bananovy-pankeiki', 'email' => 'elena@example.com', 'body' => 'На завтрак для малыша — готовится за 10 минут. Добавила корицу.'],
    ['slug' => 'sharlotka-s-jablokami', 'email' => 'cook@example.com', 'body' => 'Яблоки антоновка — лучший вариант, кислинка балансирует сладость.'],
    ['slug' => 'panna-kotta-vanilnaja', 'email' => 'anna@example.com', 'body' => 'Готовила с агар-агаром вместо желатина — тоже держится. Ягодный топпинг — огонь.'],
    ['slug' => 'syrnyj-sup-s-brokkoli', 'email' => 'dmitry@example.com', 'body' => 'Не ожидал, что брокколи так зайдёт. Жена просит на ужин раз в неделю.'],
    ['slug' => 'syrnyj-sup-s-brokkoli', 'email' => 'oleg@example.com', 'body' => 'Чуть разбавил молоком — был густоват. Вкус насыщенный.'],
    ['slug' => 'syrniki-iz-tvoroga-pyshnye', 'email' => 'maria@example.com', 'body' => 'Манка реально спасает от «течёжки». Жарю на среднем огне без спешки.'],
    ['slug' => 'lenivye-golubtsy-v-duhovke', 'email' => 'anna@example.com', 'body' => 'Удобнее классических голубцов в разы. Заливка с томатом заходит.'],
    ['slug' => 'grechka-s-kuricej-i-ovoshhami', 'email' => 'elena@example.com', 'body' => 'Быстро и сытно. Добавила замороженный горошек в конце — цвета больше.'],
    ['slug' => 'limonnyj-keks-na-kefire', 'email' => 'dmitry@example.com', 'body' => 'Кекс не сухой, лимон чувствуется. На работу нарезал на батончики.'],
];

/** Демо-сообщения формы «Контакты» (идемпотентность по уникальному subject). */
$seedContactMessages = [
    [
        'name' => 'Ирина Ковалева',
        'email' => 'guest-irina@example.com',
        'subject' => 'Демо: спасибо за рецепт борща',
        'body' => 'Здравствуйте! Готовила ваш классический борщ — получился таким же насыщенным, как в детстве. Добавьте, пожалуйста, в подсказки время активного приготовления отдельно от варки бульона. Спасибо за проект!',
        'is_read' => 1,
    ],
    [
        'name' => 'Сергей Павлов',
        'email' => 'guest-sergey@example.com',
        'subject' => 'Демо: идея — раздел «Бюджетные ужины»',
        'body' => 'Добрый день. Предлагаю завести тег или подборку рецептов до 200 рублей на порцию. Думаю, многим студентам и молодым семьям было бы полезно. Готов помочь с контентом.',
        'is_read' => 1,
    ],
    [
        'name' => 'Марина',
        'email' => 'guest-marina@example.com',
        'subject' => 'Демо: вопрос по загрузке фото',
        'body' => 'Пытаюсь добавить рецепт с телефона — после выбора файла крутится долго и ничего не происходит. Интернет стабильный, фото около 2 МБ. Что можно попробовать?',
        'is_read' => 0,
    ],
    [
        'name' => 'Алексей',
        'email' => 'guest-alex@example.com',
        'subject' => 'Демо: сотрудничество (блог о еде)',
        'body' => 'Мы ведём небольшой кулинарный канал и хотели бы обменяться ссылками или подготовить гостевой рецепт. Напишите, если вам интересен такой формат.',
        'is_read' => 0,
    ],
    [
        'name' => 'Наталья В.',
        'email' => 'guest-natalya@example.com',
        'subject' => 'Демо: опечатка в ингредиентах',
        'body' => 'В рецепте оладий на кефире, кажется, перепутаны граммы муки в тексте и в кратком списке. Проверьте, пожалуйста. Оладушки всё равно получились отличные :)',
        'is_read' => 0,
    ],
];

// ─── Выполнение сидера ────────────────────────────────────────────────────────

$log = [];
$success = false;

function seed_log(string $type, string $msg, bool $isCli): void
{
    global $log;
    $log[] = ['type' => $type, 'msg' => $msg];
    if ($isCli) {
        echo ($type === 'error' ? '[ERR] ' : ($type === 'skip' ? '[---] ' : '[OK]  ')) . $msg . "\n";
    }
}

if ($run && $dbOk) {
    $schemaPath = __DIR__ . '/sql/schema_for_hosting.sql';
    try {
        install_schema_from_file($pdo, $schemaPath);
        seed_log('ok', 'Таблицы созданы или уже есть (schema_for_hosting.sql).', $isCli);
    } catch (Throwable $ex) {
        seed_log('error', 'Не удалось применить схему: ' . $ex->getMessage(), $isCli);
        if ($isCli) {
            exit(1);
        }
    }

    if (!empty(array_filter($log, static fn (array $x): bool => $x['type'] === 'error'))) {
        // Ошибка схемы — не продолжаем сид пользователей
    } else {
        $hash = password_hash($seedPassword, PASSWORD_DEFAULT);
        $addedUsers = 0;
        $updatedUsers = 0;

        foreach ($users as $u) {
            $st = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $st->execute([$u['email']]);
            $row = $st->fetch();

            if (!$row) {
                $ins = $pdo->prepare('INSERT INTO users (email, password_hash, full_name, role) VALUES (?, ?, ?, ?)');
                $ins->execute([$u['email'], $hash, $u['full_name'], $u['role']]);
                $addedUsers++;
                seed_log('ok', "Пользователь создан: {$u['email']} (роль: {$u['role']})", $isCli);
            } elseif ($resetAdmin && $u['email'] === 'admin@example.com') {
                $up = $pdo->prepare('UPDATE users SET password_hash = ?, full_name = ?, role = ? WHERE email = ?');
                $up->execute([$hash, $u['full_name'], $u['role'], $u['email']]);
                $updatedUsers++;
                seed_log('ok', "Администратор обновлён: пароль снова «{$seedPassword}»", $isCli);
            } else {
                seed_log('skip', "Пользователь уже есть: {$u['email']} — пропуск", $isCli);
            }
        }

        $userIds = [];
        $stUserId = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        foreach ($users as $u) {
            $stUserId->execute([$u['email']]);
            $ur = $stUserId->fetch();
            if ($ur) {
                $userIds[$u['email']] = (int) $ur['id'];
            }
        }

        if (!isset($userIds['admin@example.com'])) {
            seed_log('error', 'Не найден admin@example.com после сида.', $isCli);
            if ($isCli) {
                exit(1);
            }
        } else {
            $adminId = $userIds['admin@example.com'];
            $addedPosts = 0;

            foreach ($posts as $p) {
                $chk = $pdo->prepare('SELECT id FROM posts WHERE slug = ?');
                $chk->execute([$p['slug']]);
                if ($chk->fetch()) {
                    seed_log('skip', "Рецепт уже есть: {$p['slug']} — пропуск", $isCli);
                    continue;
                }
                $wantAuthor = $p['author_email'] ?? $postAuthorBySlug[$p['slug']] ?? 'admin@example.com';
                if (!isset($userIds[$wantAuthor])) {
                    seed_log('skip', "Автор «{$wantAuthor}» не найден для «{$p['slug']}» — рецепт от админа.", $isCli);
                    $authorId = $adminId;
                    $logAuthor = 'admin@example.com';
                } else {
                    $authorId = $userIds[$wantAuthor];
                    $logAuthor = $wantAuthor;
                }
                $ins = $pdo->prepare('INSERT INTO posts (user_id, title, slug, category, image_path, excerpt, body, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $ins->execute([$authorId, $p['title'], $p['slug'], $p['category'] ?? null, $p['image_path'] ?? null, $p['excerpt'], $p['body'], $p['status'] ?? 'published']);
                $addedPosts++;
                seed_log('ok', "Рецепт добавлен: {$p['title']} (автор: {$logAuthor})", $isCli);
            }

            $addedLikes = 0;
            $stLike = $pdo->prepare('INSERT IGNORE INTO likes (post_id, user_id) VALUES (?, ?)');
            $stPostId = $pdo->prepare('SELECT id FROM posts WHERE slug = ? LIMIT 1');
            foreach ($seedLikes as $row) {
                $stPostId->execute([$row['slug']]);
                $pr = $stPostId->fetch();
                if (!$pr || !isset($userIds[$row['email']])) {
                    continue;
                }
                $stLike->execute([(int) $pr['id'], $userIds[$row['email']]]);
                if ($stLike->rowCount() > 0) {
                    $addedLikes++;
                }
            }

            $addedFavs = 0;
            $stFav = $pdo->prepare('INSERT IGNORE INTO favorites (post_id, user_id) VALUES (?, ?)');
            foreach ($seedFavorites as $row) {
                $stPostId->execute([$row['slug']]);
                $pr = $stPostId->fetch();
                if (!$pr || !isset($userIds[$row['email']])) {
                    continue;
                }
                $stFav->execute([(int) $pr['id'], $userIds[$row['email']]]);
                if ($stFav->rowCount() > 0) {
                    $addedFavs++;
                }
            }

            $addedComments = 0;
            $chkComment = $pdo->prepare('SELECT 1 FROM comments WHERE post_id = ? AND user_id = ? AND body = ? LIMIT 1');
            $insComment = $pdo->prepare('INSERT INTO comments (post_id, user_id, body) VALUES (?, ?, ?)');
            foreach ($seedComments as $row) {
                $stPostId->execute([$row['slug']]);
                $pr = $stPostId->fetch();
                if (!$pr || !isset($userIds[$row['email']])) {
                    continue;
                }
                $pid = (int) $pr['id'];
                $uid = $userIds[$row['email']];
                $chkComment->execute([$pid, $uid, $row['body']]);
                if ($chkComment->fetch()) {
                    continue;
                }
                $insComment->execute([$pid, $uid, $row['body']]);
                $addedComments++;
            }

            $addedContact = 0;
            $chkContactSub = $pdo->prepare('SELECT id FROM contact_messages WHERE subject = ? LIMIT 1');
            $insContact = $pdo->prepare(
                'INSERT INTO contact_messages (name, email, subject, body, is_read) VALUES (?,?,?,?,?)'
            );
            foreach ($seedContactMessages as $row) {
                $chkContactSub->execute([(string) $row['subject']]);
                if ($chkContactSub->fetch()) {
                    continue;
                }
                $insContact->execute([
                    $row['name'],
                    $row['email'],
                    $row['subject'],
                    $row['body'],
                    (int) ($row['is_read'] ?? 0),
                ]);
                $addedContact++;
            }
            if ($addedContact > 0) {
                seed_log('ok', "Сообщения обратной связи (демо): +{$addedContact}", $isCli);
            } elseif ($seedContactMessages !== []) {
                seed_log('skip', 'Демо-сообщения обратной связи уже есть (совпала тема) — пропуск.', $isCli);
            }

            seed_log('ok', "Готово. Пользователей: +{$addedUsers} обновлено: {$updatedUsers}, рецептов: +{$addedPosts}, лайков: +{$addedLikes}, в избранном: +{$addedFavs}, комментариев: +{$addedComments}, обратная связь (новые демо): +{$addedContact}.", $isCli);
            seed_log('ok', "Пароль для учётных записей сида: «{$seedPassword}»", $isCli);
            $success = true;
        }
    }
}

// ─── Для CLI выходим здесь ────────────────────────────────────────────────────

if ($isCli) {
    exit($success ? 0 : ($run ? 1 : 0));
}

// ─── HTML-интерфейс (только для браузера) ─────────────────────────────────────

?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Сидер — <?= e(SITE_NAME) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .seed-wrap { max-width: 600px; margin: 0 auto; }
        .log-line { padding: 0.3rem 0; border-bottom: 1px solid var(--border); font-family: monospace; font-size: 0.9rem; }
        .log-ok   { color: #2e6b32; }
        .log-skip { color: var(--muted); }
        .log-error{ color: #8c2f23; font-weight: 600; }
        .pill { display: inline-block; width: 14px; height: 14px; border-radius: 50%; margin-right: 6px; vertical-align: middle; }
        .pill-ok    { background: #4caf50; }
        .pill-skip  { background: #bdbdbd; }
        .pill-error { background: #e53935; }
    </style>
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a class="logo" href="/index.php">
            <img class="logo-img" src="<?= e(site_logo_url()) ?>" alt="" width="42" height="42">
            <span class="logo-text"><?= e(SITE_NAME) ?></span>
        </a>
        <nav class="nav"><a href="/index.php">← На главную</a></nav>
    </div>
</header>
<main class="container main-content">
<div class="seed-wrap">
    <h1>Сидер базы данных</h1>

    <?php if (!$dbOk): ?>
        <div class="alert alert-error">
            <strong>Нет подключения к БД.</strong><br>
            <?= e($dbError) ?><br><br>
            Убедитесь, что контейнеры запущены: <code>docker compose up -d</code>
        </div>
    <?php elseif ($run): ?>
        <div class="alert <?= $success ? 'alert-success' : 'alert-error' ?>">
            <?= $success ? '✓ Сид выполнен успешно.' : '✗ При выполнении возникли ошибки.' ?>
        </div>
        <div class="card" style="margin-bottom:1.5rem">
            <?php foreach ($log as $line): ?>
                <div class="log-line log-<?= e($line['type']) ?>">
                    <span class="pill pill-<?= e($line['type']) ?>"></span>
                    <?= e($line['msg']) ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="row-actions">
            <a class="btn btn-secondary" href="/seed.php">Запустить снова</a>
            <a class="btn btn-primary" href="/index.php">На главную</a>
        </div>
    <?php else: ?>
        <p class="lead">Сначала создаёт таблицы из <code>sql/schema_for_hosting.sql</code> (если их ещё нет), затем добавляет демо-пользователей, рецепты с разными авторами, лайки, избранное, комментарии и сообщения формы «Контакты». Повторный запуск безопасен: пользователи и посты по slug не дублируются; лайки и избранное — через <code>INSERT IGNORE</code>; комментарии — только если такой же текст ещё не был добавлен; демо-письма — если такой <code>subject</code> ещё не сохраняли.</p>
        <div class="card" style="margin-bottom:1.5rem">
            <h2 style="margin-top:0">Что будет создано</h2>
            <p><strong>Пользователи</strong> (если не существуют):</p>
            <ul>
                <?php foreach ($users as $u): ?>
                    <li><?= e($u['email']) ?> — <?= e($u['role'] === 'admin' ? 'Администратор' : 'Пользователь') ?>, пароль: <code><?= e($seedPassword) ?></code></li>
                <?php endforeach; ?>
            </ul>
            <p><strong>Рецепты</strong> (если slug не занят), у части указаны авторы из списка выше; без привязки — от админа:</p>
            <ul>
                <?php foreach ($posts as $p): ?>
                    <?php
                    $ae = $p['author_email'] ?? $postAuthorBySlug[$p['slug']] ?? 'admin@example.com';
                    ?>
                    <li><?= e($p['title']) ?> — автор: <code><?= e($ae) ?></code></li>
                <?php endforeach; ?>
            </ul>
            <p><strong>Активность</strong>: до <?= count($seedLikes) ?> лайков, <?= count($seedFavorites) ?> записей в избранном, <?= count($seedComments) ?> комментариев (для существующих постов с подходящими slug).</p>
            <p><strong>Обратная связь</strong>: до <?= count($seedContactMessages) ?> демо-сообщений в таблице <code>contact_messages</code> (если тема письма ещё не встречалась — смотрите в <a href="/admin/messages.php">админке</a>).</p>
        </div>
        <form method="post" action="">
            <div class="form-group">
                <label>
                    <input type="checkbox" name="reset_admin" value="1">
                    Сбросить пароль <code>admin@example.com</code> на «<?= e($seedPassword) ?>»
                </label>
            </div>
            <input type="hidden" name="run" value="1">
            <button class="btn btn-primary" type="submit">Запустить сидер</button>
        </form>
    <?php endif; ?>
</div>
</main>
<footer class="site-footer">
    <div class="container"><?= e(SITE_NAME) ?> · демо-данные для практики (PHP + MySQL)</div>
</footer>
</body>
</html>
