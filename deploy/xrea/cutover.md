# 試し印刷と切替の手順 (旧 → v2)

帰省時 (2026-10-29〜) に、店で上から順に進めるための手順書。

| | 旧 (今の本番) | v2 (新しいレジ) |
|---|---|---|
| URL | `http://cleaningpossystem.shop` | **`http://asahiya-pos.xrea.jp`** (http のみ。`dev.cleaningpossystem.shop` も同じ v2) |
| 入口 | アプリのログインだけ | **Basic 認証** (ユーザー `asahiya` と Basic 認証のパスワード) → アプリのログイン (旧と同じメールアドレス・パスワード) |
| アプリ | `~/prod/cleaning_pos` | `~/v2/cleaning_pos/v2` |
| Web ルート | `~/public_html/cleaningpossystem.shop` | `~/public_html/asahiya-pos.xrea.jp` |
| DB | `cleaningpossystem` | `cleaningpossystem_dev` |
| 自動デプロイ | cron `~/commands/pull_prod.bash` (feature/launch) | cron `~/commands/pull_v2.bash` (master) |
| Pusher | 本番のアプリ (キー b2d5…) | 切替までは dev のアプリ (キー 5f8b…)。切替で本番のアプリに替える |

Basic 認証のパスワードはこのリポジトリには書かない (オーナーの控えを見る)。

流れ: **0. 準備 → 1. 試し印刷 (営業中でもよい) → 2. 切替 (閉店後) → 3. 翌朝の確認**。だめなら **4. 戻し手順**。

---

## 0. 準備 (店に着いたら最初に)

### 0-1. SSH をつなぐ

XREA の SSH は、登録した IP からしかつながらない。店の回線の IP を調べて、XREA のコントロールパネルで SSH の接続許可に足す。

```bash
curl -s https://checkip.amazonaws.com          # 店の IP (PC で)
ssh cleaningpossystem@s239.xrea.com           # つながれば OK
```

「Error reading SSH protocol banner」や接続拒否なら、IP の登録がまだ (または切れた)。

以下のコマンドはすべて XREA の SSH の中で打つ。

### 0-2. v2 が動いているか

```bash
curl -s -o /dev/null -w '%{http_code}\n' http://asahiya-pos.xrea.jp/up                       # 401 (Basic 認証なし)
curl -s -o /dev/null -w '%{http_code}\n' -u asahiya:'<Basic 認証のパスワード>' http://asahiya-pos.xrea.jp/up   # 200
grep -n '!!!' ~/log/deploy_v2.log | tail -3        # 直近に失敗がないか (最後の行の日時を見る)
tail -3 ~/log/deploy_v2.log                         # "=== Deploy completed: ..." で終わっていること
ls -ld ~/v2/cleaning_pos/v2/storage/fonts            # drwxr-xr-x cleaningpossystem (PDF のフォントの置き場。書けること)
grep -c backoff_min ~/commands/pull_v2.bash          # 1 以上 (新しい pull_v2.bash が入っている)
grep -n '^SESSION_LIFETIME' ~/v2/cleaning_pos/v2/.env                                  # SESSION_LIFETIME=1440
(cd ~/v2/cleaning_pos/v2 && php83cli artisan config:show session.lifetime)             # lifetime ... 1440
```

`SESSION_LIFETIME` はログインが続く時間 (分)。1440 = 24 時間。120 (2 時間) のままだと、預り入力などの画面を開いたまま 2 時間ほど放っておくと保存できなくなる
(「ログインが切れました。もう一度ログインしてください」と出てログインの画面に戻る)。1440 でないときは:

```bash
cd ~/v2/cleaning_pos/v2 && cp -p .env ~/backup/v2_env_$(date +%Y%m%d%H%M%S)
sed -i 's/^SESSION_LIFETIME=.*/SESSION_LIFETIME=1440/' .env && php83cli artisan config:cache
```

### 0-3. v2 のコードを直すとき

`master` に push すると、10 分以内に XREA が自動で取り込む。**画面 (v2/resources) を変えるとビルドが走り、その間 (1〜3 分) v2 の画面が 500 になる。**
切替後は営業中に push しない (閉店後に push し、`tail ~/log/deploy_v2.log` で completed を確かめる)。`feature/launch` には push しない (旧の本番に出る)。

---

## 1. 試し印刷 (切替の前)

v2 の DB は本番のコピー (2026-10-27 時点) なので、**試しに入れた注文は切替のときの取り込み (`legacy:import --fresh`) で全部消える。** 本物のお客さんで試してよいが、旧にも同じものを入れること。
顧客表示はこの時点では dev の Pusher なので、旧の顧客表示には何も出ない (混ざらない)。

### 1-1. 店の iPad で v2 を開く

1. Safari で `http://asahiya-pos.xrea.jp` を開く (旧のブックマークはまだ消さない。新しいタブで)
2. Basic 認証の画面: ユーザー名 `asahiya`、パスワード = Basic 認証のパスワード
3. アプリのログイン: 旧と同じメールアドレス・パスワード
4. 担当者を選ぶ → ホームが出る

- [ ] ホームが出た (担当者・顧客の一覧が旧と同じ)
- [ ] メニュー → 「レシート設定」(`/receipt/edit`) のプリンタの IP が旧と同じ (旧の設定を取り込んである)

**ホーム画面のアイコンから開けるかを、ここで試しておく** (切替の夜にはじめて試すと、翌朝アイコンが真っ白で開けないことがある)。
v2 は入口に Basic 認証があり、iPad の「ホーム画面に追加」のアイコンは Safari とは別のアプリのように開く (Safari で保存したパスワードやログインを使わない)。

5. Safari の共有 → 「ホーム画面に追加」(名前「新レジ (試し)」)
6. ホーム画面の「新レジ (試し)」を開く
7. Basic 認証を聞かれたら `asahiya` / Basic 認証のパスワード。アプリのログインの画面が出たら、メールアドレス・パスワードでログインする
8. 担当者を選ぶ → ホームが出る。いったんアイコンを閉じて (アプリの切り替えで上にはらう)、もう一度開いても入れるか

- [ ] **アイコンから開けた** → 2-9 でアイコンを使う (試しのアイコンは消してよい。2-9 で作り直す)
- [ ] **開けなかった** (真っ白・何も出ない・Basic 認証の画面が出ない・何度もログインに戻る) → アイコンは消して、**レジは Safari のブックマークから開く** (2-9 の「アイコンが使えないとき」)。旧のアイコンも切替のあとは使わない

### 1-2. 顧客表示の端末で v2 の顧客表示を開く (閉店後、または旧の表示を少し止めてよいとき)

```bash
cat ~/backup/display_token.txt     # 2026-10-27 に発行した表示端末の URL (http://asahiya-pos.xrea.jp/display/login/...)
```

1. 表示端末のブラウザでこの URL を開く → Basic 認証 (`asahiya` / Basic 認証のパスワード) → 顧客表示の画面になる
2. 「顧客表示の再ログインが必要です（スタッフ向け）」と出たら、URL が古い。`php83cli artisan display:issue-token` で出し直す (下の 2-8)

### 1-3. 注文 → 会計 → レシート → ドロア → 顧客表示

| | やること | 見ること |
|---|---|---|
| ☐ | ホームで顧客を選ぶ | 表示端末に顧客名が出る |
| ☐ | 預りを入力 (衣類を 2〜3 点) | タグ番号が `X-XXX` で続き番号になる |
| ☐ | 会計 → 現金で支払い | 画面の合計・内消費税・お釣りが正しい |
| ☐ | レシートが出る | 店名・住所・電話・顧客名・担当者・品物・タグ・金額が旧のレシートと同じ並び |
| ☐ | ドロアが開く | 現金払いの初回だけ開く (請求書払い・再発行・未収では開かない。旧と同じ) |
| ☐ | 会計後の「終了」 | 表示端末が待ち受けの画面に戻る |
| ☐ | 履歴 → 伝票の詳細 → 「レシート再発行」 | 同じレシートが出て、ドロアは開かない |
| ☐ | 割引 (%) を付けた注文を 1 件 | レシートの「小計」「(うち消費税 …)」の値を控える (下の 1-5) |
| ☐ | 請求書払いの顧客で 1 件 | レシートの下に「請求書払い」 |

### 1-4. 帳票

| | やること | 見ること |
|---|---|---|
| ☐ | 請求書 → 顧客を選んで PDF | PDF が開く (初回は数秒かかる)。先月の紙の請求書と、期間・品物・小計・消費税・合計を見比べる (Q1) |
| ☐ | 日報 → 今日の PDF | PDF が開く。今日の件数・金額が画面と合う |
| ☐ | 納品書 → 取引先を選んで PDF | PDF が開く。先月の紙の納品書と、消費税の端数を見比べる (Q1) |
| ☐ | 入金確認 (`/invoice/payment_confimation`) | 下の Q2 を見る |

### 1-5. 決めること (Q1〜Q3 と、レシートの 2 か所)

いまは「仮」で作ってある。紙と見比べて、家族に聞いて決める。決めた結果を `e2e/allowed-diffs.md` の「仮」の行に書く。

- [ ] **Q1. 消費税の端数**
  - 仮: 請求書 = 四捨五入、納品書 = 切り捨て、**会計画面の内消費税 = 請求書と同じ四捨五入** (旧の画面は切り捨て)
  - 見比べ方: 税込 505 円のように端数が .5 以上になる金額で、会計画面の内消費税を旧と v2 で見る (旧 45 円 / v2 46 円になる)。紙の請求書・納品書の消費税と、v2 の PDF の消費税が同じか
  - 変えるとき: `~/v2/cleaning_pos/v2/.env` に `POS_TAX_ROUNDING_INVOICE=floor` (または `round`)、`POS_TAX_ROUNDING_DELIVERY=round` (または `floor`) を書き、`cd ~/v2/cleaning_pos/v2 && php83cli artisan config:cache`。過去の請求書の値は変わらない (取り込んだ値のまま)
- [ ] **Q2. 入金確認ありのお客さんの請求の `paid_at` (入金日)**
  - 仮 (旧の注文経由と同じ): 入金確認ありのお客さんの請求書は、作ったときに `paid_at = 期間の終わりの日` が入る。入金確認の画面は `paid_at` が空の請求書だけを出すので、**入金確認ありのお客さんの請求書は入金確認の画面に出ない** (旧と同じ動き)
  - 入金確認の画面で「入金確認」を押すと請求書に入金日が入る。**入れた金額はどこにも残らない** (旧も残していない)
  - 見比べ方: 旧と v2 で入金確認の画面を開き、同じお客さん・同じ月が出ているか。家族に「入金確認の画面をいつ・どう使っているか」「入金確認ありのお客さんはどこで入金を確かめているか」を聞く
  - 変えるとき: コードの変更 (`v2/app/Domain/Billing/InvoiceService.php` の `findOrCreateFor`) と、まだ入金していない請求書の `paid_at` の直し。切替後でもよい (切替後に作る請求書だけに効く)
- [ ] **Q3. タグ番号の戻り先**
  - 仮: 1-000〜10-999 で、10-999 の次は 1-000
  - 見比べ方: 実際のタグの札に書いてある番号の範囲を見る。家族に「10-999 まで行ったら次は何番の札か」を聞く
  - 変えるとき: `v2/config/pos.php` の `tag.min` / `tag.max` (コードの変更)
- [ ] **レシートの「小計」**
  - 今 (旧と同じ): 小計 = 伝票合計 + 割引の**割合の数字** (例: 1,000 円を 10% 割引 → 伝票合計 900、小計 **910**)。割引なしなら小計 = 伝票合計 (0 円なら空欄)
  - 旧の紙のレシート (割引あり) で同じになっているか見て、このままでよいか / 「割引前の金額 (1,000)」にするかを決める
- [ ] **レシートの「(うち消費税 …）」**
  - 今 (旧と同じ): 消費税の**金額ではなく税率の数字**が出る (例: 「(うち消費税 10）」)
  - このままでよいか / 金額 (例: 「(うち消費税 82円）」) にするかを決める
  - 小計・消費税の印字を変えるときは `v2/resources/js/printer/receiptCommands.js` (コードの変更。切替後でもよい)

試し印刷で、レシートが出ない・ドロアが開かない・PDF が開かないなどがあれば、**切替しない** (旧のまま。直してから改めて)。

---

## 2. 切替 (閉店後)

全部で 30 分ほど。**旧の画面で全員の作業が終わってから始める** (止めている間に旧に入れたものは v2 に入らない)。
上から順に。各手順の「→」が期待する結果。違ったら先に進まず、4. 戻し手順へ。

### 2-1. 控えを取る

```bash
TS=$(date +%Y%m%d%H%M); mkdir -p ~/backup
crontab -l > ~/backup/crontab_before_cutover_$TS.txt
cp -p ~/v2/cleaning_pos/v2/.env ~/backup/v2_env_before_cutover_$TS && chmod 600 ~/backup/v2_env_before_cutover_$TS
# 旧の本番 DB を丸ごと控える (読むだけ。1 分ほど)
(cd ~/prod/cleaning_pos && MYSQL_PWD="$(sed -n 's/^DB_PASSWORD=//p' .env | tr -d "\"'")" mysqldump --single-transaction --no-tablespaces \
  -h "$(sed -n 's/^DB_HOST=//p' .env)" -u "$(sed -n 's/^DB_USERNAME=//p' .env)" cleaningpossystem) | gzip > ~/backup/prod_db_before_cutover_$TS.sql.gz
ls -la ~/backup/*before_cutover_$TS*
```

→ 3 つのファイルができ、`prod_db_before_cutover_*.sql.gz` の大きさが 0 でない (`gunzip -c ... | tail -1` が `-- Dump completed on ...`)。

```bash
grep -n '^SESSION_LIFETIME' ~/v2/cleaning_pos/v2/.env      # SESSION_LIFETIME=1440 (違えば 0-2 の手順で直してから進む)
```

`$TS` は下の手順でも使う。途中で SSH が切れたら、つなぎ直して `TS=<上の ls に出た日時 (例 202610291930)>` を打ち直してから続ける。

### 2-2. 旧の自動デプロイ (cron) を止める (旧を止めるより先に)

```bash
crontab -l | sed 's|^\(\*/10 .*/pull_prod\.bash\)$|#CUTOVER# \1|' | crontab -
crontab -l | grep pull_prod
```

→ `#CUTOVER# */10 * * * * /bin/bash /virtual/cleaningpossystem/commands/pull_prod.bash` (先頭に `#CUTOVER#` が付いた)。

### 2-3. 旧を止める (メンテナンス中にする)

```bash
bash ~/v2/cleaning_pos/deploy/xrea/freeze_legacy.bash
curl -s -o /dev/null -w '%{http_code}\n' http://cleaningpossystem.shop/login
```

→ `止めました: /virtual/cleaningpossystem/public_html/cleaningpossystem.shop/.htaccess` と出て、curl が **503**。
iPad の旧の画面を再読み込みすると「メンテナンス中です」。

### 2-4. 旧の DB を v2 に取り込む

```bash
cd ~/v2/cleaning_pos/v2
php83cli artisan legacy:import --fresh 2>&1 | tee ~/backup/cutover_import_$TS.txt | tail -5
```

→ 表の最後が `delivery_email_logs` の行で、`取り込み完了。php artisan legacy:verify で検算してください。` (数十秒〜数分)。
v2 の DB は作り直しになり、試し印刷で入れた注文・表示端末の URL・端末の自動ログイン URL は消える (下で出し直す)。

### 2-5. 検算

```bash
php83cli artisan legacy:verify 2>&1 | tee ~/backup/cutover_verify_$TS.txt | tail -3
```

→ 最後の行が **`OK: 旧 DB と v2 DB は一致しています。`**。
`NG: 一致しない項目があります。` なら、`grep -v OK ~/backup/cutover_verify_$TS.txt` で違う行を見る。直せなければ 4. 戻し手順 (この時点なら 4-2〜4-4 だけでよい)。

### 2-6. 切替の時刻を控える

```bash
date '+%Y-%m-%d %H:%M:%S' | tee ~/backup/cutover_time.txt
```

→ 例 `2026-10-29 19:42:10`。**これより後に v2 で入れたものが、戻すときに手で入れ直す対象になる** (4-5)。

### 2-7. Pusher を本番のキーに替える

```bash
bash ~/v2/cleaning_pos/deploy/xrea/use_prod_pusher.bash
```

→ 次のように出る (数分かかる。途中で v2 の画面は 500 になる):

```
v2 の今のキー: 5f8b... / 旧の本番のキー: b2d5... (cluster ap3)
控え: /virtual/cleaningpossystem/backup/v2_env_<日時>
書き換えました: /virtual/cleaningpossystem/v2/cleaning_pos/v2/.env
  PUSHER_APP_KEY=b2d5...  (ほか)
作り直します (pull_v2.bash -f。数分かかります)...
OK: ビルドした JS に本番のキー (b2d5...) が入っています。
```

`tail -3 ~/log/deploy_v2.log` → `=== Deploy completed: ... ===`。config:cache も pull_v2 が流している。
cron の自動デプロイと重なったときは、スクリプトが終わるのを待ってからやり直す (「終わるまで待ちます」「1 分待ってやり直します」と出る。そのまま待つ)。
`NG: ビルドした JS に本番のキーが見つかりません` が出たら、**1〜2 分待ってから、もう一度 `bash ~/v2/cleaning_pos/deploy/xrea/use_prod_pusher.bash`** を流す (2 回目は「すでに本番のキーです」と出て、作り直しだけする)。
それでも `NG:` なら `tail -50 ~/log/deploy_v2.log` を見る。

### 2-8. 表示端末の URL と、レジ iPad の自動ログイン URL を出し直す

```bash
cd ~/v2/cleaning_pos/v2
php83cli artisan display:issue-token | tee ~/backup/display_token.txt; chmod 600 ~/backup/display_token.txt
php83cli artisan device:issue 'レジ iPad' | tee ~/backup/device_url_regi.txt; chmod 600 ~/backup/device_url_regi.txt
```

→ `発行しました: store=1 ...` のあとに
`http://asahiya-pos.xrea.jp/display/login/<長い文字列>` と `http://asahiya-pos.xrea.jp/login/device/<長い文字列>`。
URL はこの 1 回しか出ないので、ファイルに控えてある (`cat ~/backup/display_token.txt`)。iPad が 2 台以上あれば `device:issue '<名前>'` を台数分。

- 出し直しても、すでにログインしている表示端末はそのまま。止めたいときは店舗の POS のパスワードを変える (表示端末もログアウトされる)。
- 端末の URL を取り消すとき: `php83cli artisan device:revoke <id>` (id は発行のときに出る)。

### 2-9. レジの iPad

1. Safari で、2-8 の `http://asahiya-pos.xrea.jp/login/device/...` を開く (AirDrop・メモなどで iPad に送る)
2. Basic 認証: ユーザー名 `asahiya`、パスワード = Basic 認証のパスワード。**「パスワードを保存」と聞かれたら保存する**
3. そのままホーム画面に入る (メールアドレス・パスワードの入力なし)
4. Safari のブックマークに追加する (名前「レジ」など)。**1-1 でアイコンから開けたときだけ**、共有 → 「ホーム画面に追加」もする (名前「レジ」など)。アイコンから開いてログインの画面が出たら、メールアドレス・パスワードでログインする (アイコンは Safari のログインを使わないため)
5. 旧のブックマーク・ホーム画面のアイコン (`cleaningpossystem.shop`) は**消さずに名前を「旧レジ (使わない)」に変える** (戻すときに使う)。開いても「メンテナンス中」

`device:issue` を使わないときは、`http://asahiya-pos.xrea.jp` をブックマークし、Basic 認証のあとでメールアドレス・パスワードでログインする。

**アイコンが使えないとき** (1-1 で開けなかった、または今夜アイコンから開けない): アイコンは使わず、**Safari を開いて、ブックマークの「レジ」から開く**。家族にも「明日からは Safari のブックマークの『レジ』」と伝える。

- [ ] 毎朝開くもの (アイコン、または Safari のブックマーク) から開いて、担当者選択 → ホームまで行ける。一度閉じて開き直しても入れる
- [ ] 履歴 → 伝票の詳細 → 「レシート再発行」でレシートが出る (取り込んだ本物の伝票。新しいデータは増えない)

### 2-10. 顧客表示の端末

1. 2-8 の `http://asahiya-pos.xrea.jp/display/login/...` を開く
2. Basic 認証 (`asahiya` / Basic 認証のパスワード。保存する)
3. 顧客表示の画面になったら、**この `/display/login/...` の URL をブラウザのブックマークに追加**する (`/customer_display` ではなく、ログインの URL の方)。iPad で「ホーム画面に追加」も使いたいときは、アイコンから開いて顧客表示の画面になるかをその場で試す (ならなければブックマークだけを使う)
4. ふだんは開きっぱなし。「顧客表示の再ログインが必要です（スタッフ向け）」と出たら、このブックマークを開き直す

- [ ] レジの iPad でホームの顧客を選ぶ → 表示端末に顧客名が出る (本番の Pusher で届いている)
- [ ] 預りの入力を開く → 表示端末の画面が切り替わる

### 2-11. 締め

```bash
curl -s -o /dev/null -w '%{http_code}\n' http://cleaningpossystem.shop/login                                 # 503 (旧は止まったまま)
curl -s -o /dev/null -w '%{http_code}\n' -u asahiya:'<Basic 認証のパスワード>' http://asahiya-pos.xrea.jp/login   # 200
crontab -l | grep -E 'pull_(prod|v2)'      # pull_prod は #CUTOVER# 付き、pull_v2 はそのまま
```

試しに注文を入れたら、その伝票は取り消しておく (戻すときの一覧 4-5 には「取り消し済み」として出る)。

---

## 3. 翌朝の確認 (営業開始のとき、家族と一緒に)

- [ ] レジの iPad で、いつもの入口 (ホーム画面のアイコン、またはアイコンが使えないときは Safari のブックマーク「レジ」) から開ける (Basic 認証を聞かれたら `asahiya` / Basic 認証のパスワード)。アイコンが真っ白なら、Safari のブックマークから開く
- [ ] 表示端末が待ち受けの画面 (「再ログインが必要です」なら 2-10 のブックマークを開き直す)
- [ ] 最初のお客さん: 顧客を選ぶ → 表示に出る → 預り → 会計 → レシート → ドロアが開く → 「終了」で表示が戻る
- [ ] お渡し (返却) を 1 件 → 履歴でお渡し済みになる
- [ ] 請求書払いのお客さんが来たら、その請求書の PDF を 1 回開く

知っておくこと:

- 「ログインが切れました。もう一度ログインしてください」と出たら、ログインし直して、同じ操作をもう一度する (24 時間ほど画面を開いたままにすると出る。保存はされていない)
- 「入金確認を記録できませんでした」「タグ番号を変更できませんでした」と出たら、保存されていない。もう一度押す
- 請求書の画面で月を選んでも、**取り込んだ古い請求書のうち終わりの日が `-00` のもの (例: 請求書 1281・1289 の `2026-07-00` / `2026-08-00`) は出てこない**。旧でも出なかったもので、消えたわけではない (探さなくてよい)

SSH で:

```bash
grep -n '!!!' ~/log/deploy_v2.log | tail -3                                   # 切替後の日時の行がない
tail -30 ~/v2/cleaning_pos/v2/storage/logs/laravel.log                         # 切替後に ERROR がない (ファイルが無ければそれでよい)
v2sql() { (cd ~/v2/cleaning_pos/v2 && MYSQL_PWD="$(sed -n 's/^DB_PASSWORD=//p' .env | tr -d "\"'")" mysql -h "$(sed -n 's/^DB_HOST=//p' .env)" \
  -u "$(sed -n 's/^DB_USERNAME=//p' .env)" "$(sed -n 's/^DB_DATABASE=//p' .env)" --default-character-set=utf8mb4 -t "$@"); }
v2sql -e "select count(*) as orders_today from orders where created_at >= curdate()"
v2sql -e "select created_at, error_type, left(message, 120) as message from client_errors where created_at >= curdate() order by id desc limit 20"   # 画面のエラー (無いのがよい)
```

1 週間ほど問題がなければ:

- `crontab -l` の `#CUTOVER#` の行 (旧の自動デプロイ) は消してよい
- `cleaningpossystem.shop` のドメインは更新しないで手放す (期限はオーナーが確認)。`dev.cleaningpossystem.shop` も一緒に無くなる。v2 は `asahiya-pos.xrea.jp` で動き続ける
- `deploy/xrea/README.md` の「新旧比較の E2E を XREA の v2 に流す」は使えなくなっている (v2 の DB が本番になったので `e2e:seed` は動かない)

---

## 4. 戻し手順 (v2 をやめて旧に戻す)

レシートが出ない・会計ができないなどで、その場で直せないとき。営業中でも 10 分ほどで戻せる。
2-6 (切替の時刻) より前に止めたときは、v2 にはまだ何も入っていないので 4-2〜4-4 だけでよい。

### 4-1. v2 を止める (これ以上 v2 に入らないように)

```bash
bash ~/v2/cleaning_pos/deploy/xrea/freeze_legacy.bash ~/public_html/asahiya-pos.xrea.jp
bash ~/v2/cleaning_pos/deploy/xrea/freeze_legacy.bash ~/public_html/dev.cleaningpossystem.shop
curl -s -o /dev/null -w '%{http_code}\n' -u asahiya:'<Basic 認証のパスワード>' http://asahiya-pos.xrea.jp/login   # 503
date '+%Y-%m-%d %H:%M:%S' | tee ~/backup/rollback_time.txt
```

(スクリプトの名前は freeze_legacy だが、Web ルートを渡せばどこでも止められる。v2 を開け直すときは同じ Web ルートで `unfreeze_legacy.bash`。)

### 4-2. 旧を開ける

```bash
bash ~/v2/cleaning_pos/deploy/xrea/unfreeze_legacy.bash
curl -s -o /dev/null -w '%{http_code}\n' http://cleaningpossystem.shop/login
```

→ `開けました: ...`、`freeze の前と同じ中身です (...)`、curl が **200**。
`注意: freeze の前の控え ... と中身が違います` と出たら `diff ~/public_html/cleaningpossystem.shop/.htaccess.before-freeze ~/public_html/cleaningpossystem.shop/.htaccess` を見る (止めている間に誰かが変えていなければ出ない)。
スクリプトが使えないときの手作業: `cp -p ~/public_html/cleaningpossystem.shop/.htaccess.before-freeze ~/public_html/cleaningpossystem.shop/.htaccess`

### 4-3. 旧の自動デプロイ (cron) を戻す

```bash
crontab -l | sed 's|^#CUTOVER# ||' | crontab -
crontab -l | grep pull_prod
```

→ `*/10 * * * * /bin/bash /virtual/cleaningpossystem/commands/pull_prod.bash` (`#CUTOVER#` が消えた)。
うまくいかなければ控えから: `crontab ~/backup/crontab_before_cutover_<日時>.txt`

### 4-4. 端末を旧に戻す

- レジの iPad: 「旧レジ (使わない)」にしたアイコン・ブックマーク (`cleaningpossystem.shop`) を開く。名前を戻し、v2 のアイコンは「新レジ (使わない)」にする
- 顧客表示の端末: 旧の顧客表示 (`http://cleaningpossystem.shop/customer_display`) を開く
- 旧の顧客表示は本番の Pusher の公開チャンネル、v2 は店舗ごとの private チャンネルなので、v2 の Pusher を本番のキーのままにしても混ざらない。dev のキーに戻すなら `cp -p ~/backup/v2_env_before_cutover_<日時> ~/v2/cleaning_pos/v2/.env && /bin/bash ~/commands/pull_v2.bash -f`

### 4-5. 切替のあとに v2 に入れたものを一覧にして、旧に手で入れ直す

```bash
v2sql() { (cd ~/v2/cleaning_pos/v2 && MYSQL_PWD="$(sed -n 's/^DB_PASSWORD=//p' .env | tr -d "\"'")" mysql -h "$(sed -n 's/^DB_HOST=//p' .env)" \
  -u "$(sed -n 's/^DB_USERNAME=//p' .env)" "$(sed -n 's/^DB_DATABASE=//p' .env)" --default-character-set=utf8mb4 -t "$@"); }
T=$(cat ~/backup/cutover_time.txt); echo "切替の時刻: $T"
(echo "SET @t = '$T';"; cat ~/v2/cleaning_pos/deploy/xrea/since_cutover.sql) | v2sql > ~/backup/since_cutover.txt
less -S ~/backup/since_cutover.txt          # 横に長いので -S。PC にコピーするなら scp で
```

一覧は `orders.created_at >= 切替の時刻` などで出している (`deploy/xrea/since_cutover.sql`。読むだけ):

| 節 | 中身 | 旧でやること |
|---|---|---|
| 1・2 | 切替後の新しい預り (注文) と品物・タグ | 同じ顧客・担当者・品物・タグで預りを入れ直す (タグ番号は 8 にそろえてから) |
| 3・4 | 切替前の注文で、切替後にお渡し・支払い・取り消し・タグ変更したもの | 旧で同じ操作をする |
| 5 | 請求書 (作成・入金確認・発行) | 入金確認・発行を旧でもやる (請求書そのものは 1 の注文を入れれば旧が作る) |
| 6・7 | 新しく登録した・変えた顧客と顧客メモ | 旧で登録・変更する (1 より先に) |
| 8 | タグ番号の今の値 (担当者ごと) | 旧のホームの「タグ番号更新」で、切替後に v2 で使ったタグの続きになるようにそろえる |
| 9 | 衣類マスタの変更 | 旧の衣類マスタで同じに |
| 10〜12 | 納品書の記帳・発行・メール・マスタ | 旧の納品書の画面で入れ直す (メール送信済みなら送り直さない) |

`deleted_at` が入っている行は v2 で取り消したもの (旧では入れない、または入れて取り消す)。
入れ直しが終わったら、v2 は止めたまま (4-1) にしておく。もう一度切り替えるときは、直してから 2. をはじめからやり直す (2-4 の `--fresh` で旧の最新を取り込み直すので、v2 の古いデータは消えてよい)。

---

## 付録: スクリプト

| ファイル | 使い方 |
|---|---|
| `deploy/xrea/freeze_legacy.bash [Web ルート]` | `.htaccess` の先頭に「メンテナンス中」(503) を足す。既定は旧の本番の Web ルート。控えは `.htaccess.before-freeze` と `~/backup/htaccess_<名前>_<日時>`。二度流しても一度だけ足す |
| `deploy/xrea/unfreeze_legacy.bash [Web ルート]` | 足したところを消し、控えと同じ中身になったか確かめる。止めていなければ何もしない |
| `deploy/xrea/use_prod_pusher.bash` | v2 の `.env` を控えてから、旧の本番の `PUSHER_*` を写し (`VITE_PUSHER_APP_KEY` / `CLUSTER` も)、`pull_v2.bash -f` で作り直す |
| `deploy/xrea/since_cutover.sql` | 戻すときに、切替後に v2 で作られた・変わった行を出す (SELECT だけ) |
| `deploy/xrea/pull_v2.bash` | v2 の自動デプロイ (cron)。失敗したときの動きは `README.md` |

2026-10-27 に dev (`~/public_html/dev.cleaningpossystem.shop`) で試した結果: freeze で 503 (Basic 認証なしは 401 のまま)、unfreeze で 200、`.htaccess` は freeze の前と同じ中身に戻った。そのあいだ `asahiya-pos.xrea.jp` と `cleaningpossystem.shop` は 200 のまま。
