<template>
  <div class="col-12 px-3 mt-2">
    <h5>{{ customer.name }} 納品書管理</h5>

    <!-- 期間指定 -->
    <div class="card mb-3">
      <div class="card-body">
        <div class="row align-items-end">
          <div class="col-md-3">
            <label class="form-label">開始日</label>
            <input type="date" class="form-control" v-model="periodStart">
          </div>
          <div class="col-md-3">
            <label class="form-label">終了日</label>
            <input type="date" class="form-control" v-model="periodEnd">
          </div>
          <div class="col-md-3">
            <button class="btn btn-outline-primary w-100" @click="fetchPreview" :disabled="!periodStart || !periodEnd">
              集計プレビュー
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- 警告（確定済エントリ含む） -->
    <div v-if="hasFinalized" class="alert alert-warning">
      ⚠️ この期間にはすでに別の納品書に含まれているデータがあります。
    </div>

    <!-- 集計プレビュー -->
    <div v-if="previewData.length > 0" class="card mb-3">
      <div class="card-body">
        <h6>集計結果</h6>
        <table class="table table-bordered table-sm">
          <thead class="table-light">
            <tr><th>No</th><th>品名</th><th>数量</th><th>単価</th><th>金額(税抜)</th></tr>
          </thead>
          <tbody>
            <template v-for="dept in previewData">
              <tr class="table-secondary">
                <td></td><td colspan="4"><strong>▼ {{ dept.name }}</strong></td>
              </tr>
              <tr v-for="product in dept.products" :key="product.name">
                <td></td>
                <td>　{{ product.name }}</td>
                <td class="text-end">{{ product.quantity.toLocaleString() }}</td>
                <td class="text-end">{{ product.unit_price.toLocaleString() }}</td>
                <td class="text-end">{{ product.amount.toLocaleString() }}</td>
              </tr>
              <tr>
                <td></td><td class="text-end"><strong>小計</strong></td>
                <td></td><td></td>
                <td class="text-end"><strong>{{ dept.subtotal.toLocaleString() }}</strong></td>
              </tr>
            </template>
          </tbody>
        </table>

        <!-- 税計算 -->
        <div class="text-end mt-2">
          <div v-for="tax in taxGroups" :key="tax.rate">
            税率 {{ (tax.rate * 100).toFixed(0) }}%　消費税: {{ tax.tax.toLocaleString() }}円　税抜合計: {{ tax.subtotal_excl.toLocaleString() }}円
          </div>
          <div class="h5 mt-2">税込合計: {{ totalIncl.toLocaleString() }} 円</div>
        </div>

        <!-- PDF・メールボタン -->
        <div class="d-flex gap-2 mt-3">
          <a :href="`/delivery/notes/${customer.id}/pdf?period_start=${periodStart}&period_end=${periodEnd}`"
             target="_blank" rel="noopener noreferrer" class="btn btn-danger">
            📄 PDF表示
          </a>
          <button class="btn btn-outline-success" :disabled="!customer.email" @click="showEmailForm = true">
            📧 メール送信 {{ !customer.email ? '（メアド未設定）' : '' }}
          </button>
        </div>
      </div>
    </div>

    <!-- メール送信フォーム -->
    <div v-if="showEmailForm" class="card mb-3">
      <div class="card-body">
        <h6>メール送信確認</h6>
        <p>送信先: <strong>{{ customer.email }}</strong></p>
        <label class="form-label">本文</label>
        <textarea class="form-control mb-3" rows="8" v-model="emailBody"></textarea>
        <div class="d-flex gap-2">
          <button class="btn btn-success" @click="sendEmail">送信する</button>
          <button class="btn btn-outline-secondary" @click="showEmailForm = false">キャンセル</button>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
export default {
  props: {
    customer: { type: Object, required: true },
    token: { type: String, required: true },
  },
  data() {
    const today = new Date().toISOString().slice(0, 10);
    const firstDay = today.slice(0, 7) + '-01';
    return {
      periodStart: firstDay,
      periodEnd: today,
      previewData: [],
      taxGroups: [],
      hasFinalized: false,
      showEmailForm: false,
      emailBody: '',
      noteId: null,
      csrf: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
    };
  },
  computed: {
    totalIncl() {
      return this.taxGroups.reduce((sum, g) => sum + g.subtotal_incl, 0);
    },
  },
  methods: {
    async fetchPreview() {
      try {
        const res = await axios.get('/api/delivery/preview', {
          params: {
            customer_id: this.customer.id,
            period_start: this.periodStart,
            period_end: this.periodEnd,
          },
          headers: { Authorization: `Bearer ${this.token}` },
          withCredentials: true,
        });
        this.previewData = res.data.entries;
        this.taxGroups = res.data.tax_groups;
        this.hasFinalized = res.data.has_finalized;
        this.noteId = res.data.note_id;
        if (this.customer.email) this.emailBody = res.data.default_email_body;
      } catch (e) {
        alert('集計に失敗しました');
      }
    },
    async sendEmail() {
      if (!confirm('メールを送信しますか？')) return;
      try {
        await axios.post(`/delivery/notes/${this.customer.id}/email`, {
          note_id: this.noteId,
          body: this.emailBody,
          _token: this.csrf,
        }, { withCredentials: true });
        alert('送信しました');
        this.showEmailForm = false;
      } catch (e) {
        alert('送信に失敗しました');
      }
    },
  },
};
</script>
