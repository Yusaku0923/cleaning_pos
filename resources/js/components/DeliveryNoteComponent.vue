<template>
  <div class="col-12 px-3 mt-2">
    <h5>{{ customer.name }} 納品書管理</h5>

    <!-- 日次入力 -->
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center" style="cursor:pointer;" @click="entryPanelOpen = !entryPanelOpen">
        <span>日次入力</span>
        <span>{{ entryPanelOpen ? '▲' : '▼' }}</span>
      </div>
      <div v-if="entryPanelOpen" class="card-body">

        <!-- 日付一覧 -->
        <div v-if="!selectedDate">
          <div class="d-flex align-items-center gap-2 mb-3">
            <input type="date" v-model="pickerDate" class="form-control" style="flex:1;">
            <button class="btn btn-primary" style="white-space:nowrap;" @click="selectDate(pickerDate)">入力</button>
          </div>
          <div v-if="loadingDates" class="text-center py-2">読み込み中...</div>
          <div v-else>
            <div v-for="d in entryDates" :key="d.date" class="card mb-2 p-2">
              <div class="d-flex align-items-center justify-content-between">
                <span style="flex:1; cursor:pointer;" @click="selectDate(d.date)">{{ formatDate(d.date) }}</span>
                <span v-if="d.finalized" class="text-primary me-2">✅ 確定済</span>
                <span v-else class="text-success me-2" style="cursor:pointer;" @click="selectDate(d.date)">✅ 入力済</span>
                <button class="btn btn-outline-danger btn-sm" @click.stop="deleteEntryDate(d.date)">削除</button>
              </div>
            </div>
            <div v-if="entryDates.length === 0" class="text-muted text-center py-2">入力済みデータはありません</div>
          </div>
        </div>

        <!-- 日次入力フォーム -->
        <div v-else>
          <div class="d-flex align-items-center mb-3">
            <button class="btn btn-outline-secondary me-3" @click="selectedDate = null">← 戻る</button>
            <strong>{{ formatDate(selectedDate) }} の記帳</strong>
          </div>
          <div v-if="loadingEntry" class="text-center py-2">読み込み中...</div>
          <div v-else>
            <div v-for="dept in customer.departments" :key="dept.id" class="mb-3">
              <div class="bg-secondary text-white px-3 py-1 mb-1 rounded" style="font-weight:bold;">{{ dept.name }}</div>
              <div v-for="product in dept.products" :key="product.id" class="card mb-2 p-2">
                <div class="d-flex align-items-center justify-content-between">
                  <span style="flex:1;">{{ product.name }}</span>
                  <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-outline-secondary btn-sm" style="width:36px;height:36px;" @click="adjust(product.id, -1)">－</button>
                    <input type="text" inputmode="numeric" pattern="[0-9]*"
                      :value="quantities[product.id] || 0"
                      @focus="$event.target.select()"
                      @change="setQuantity(product.id, $event.target.value)"
                      style="width:60px;height:36px;font-size:18px;text-align:center;border:2px solid #ccc;border-radius:6px;">
                    <button class="btn btn-outline-secondary btn-sm" style="width:36px;height:36px;" @click="adjust(product.id, 1)">＋</button>
                  </div>
                </div>
              </div>
            </div>
            <button class="btn btn-success w-100" :disabled="savingEntry" @click="saveEntries">
              {{ savingEntry ? '保存中...' : '保存する' }}
            </button>
          </div>
        </div>

      </div>
    </div>

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
            <label class="form-label">締め日</label>
            <input type="date" class="form-control" v-model="cutoffDate">
          </div>
          <div class="col-md-3">
            <button class="btn btn-outline-primary w-100 mt-3" @click="fetchPreview" :disabled="!periodStart || !periodEnd">
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
          <a :href="`/delivery/notes/${customer.id}/pdf?period_start=${periodStart}&period_end=${periodEnd}&cutoff_date=${cutoffDate}`"
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
    const cutoffDefault = today.slice(0, 7) + '-25';
    return {
      periodStart: firstDay,
      periodEnd: today,
      cutoffDate: cutoffDefault,
      previewData: [],
      taxGroups: [],
      hasFinalized: false,
      showEmailForm: false,
      emailBody: '',
      noteId: null,
      csrf: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      // 日次入力
      entryPanelOpen: false,
      pickerDate: today,
      selectedDate: null,
      quantities: {},
      entryDates: [],
      loadingDates: false,
      loadingEntry: false,
      savingEntry: false,
    };
  },
  computed: {
    totalIncl() {
      return this.taxGroups.reduce((sum, g) => sum + g.subtotal_incl, 0);
    },
  },
  watch: {
    entryPanelOpen(val) {
      if (val && this.entryDates.length === 0) this.fetchEntryDates();
    },
    selectedDate(val) {
      if (val) this.fetchEntries();
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
    formatDate(dateStr) {
      const d = new Date(dateStr + 'T00:00:00');
      return `${d.getMonth() + 1}月${d.getDate()}日（${'日月火水木金土'[d.getDay()]}）`;
    },
    selectDate(date) {
      this.selectedDate = date;
    },
    async fetchEntryDates() {
      this.loadingDates = true;
      try {
        const res = await axios.get('/api/delivery/entry-status', {
          params: { customer_id: this.customer.id },
          headers: { Authorization: `Bearer ${this.token}` },
          withCredentials: true,
        });
        this.entryDates = res.data.dates.sort((a, b) => b.date.localeCompare(a.date));
      } finally {
        this.loadingDates = false;
      }
    },
    async fetchEntries() {
      this.loadingEntry = true;
      this.quantities = {};
      try {
        const res = await axios.get('/api/delivery/entries', {
          params: { customer_id: this.customer.id, date: this.selectedDate },
          headers: { Authorization: `Bearer ${this.token}` },
          withCredentials: true,
        });
        res.data.entries.forEach(e => {
          this.$set(this.quantities, e.delivery_product_id, e.quantity);
        });
      } finally {
        this.loadingEntry = false;
      }
    },
    adjust(productId, delta) {
      const current = this.quantities[productId] || 0;
      this.$set(this.quantities, productId, Math.max(0, current + delta));
    },
    setQuantity(productId, value) {
      const num = parseInt(value, 10);
      this.$set(this.quantities, productId, isNaN(num) || num < 0 ? 0 : num);
    },
    async deleteEntryDate(date) {
      if (!confirm(`${this.formatDate(date)} のデータを削除しますか？`)) return;
      try {
        await axios.delete('/api/delivery/entries', {
          data: { customer_id: this.customer.id, date },
          headers: { Authorization: `Bearer ${this.token}` },
          withCredentials: true,
        });
        await this.fetchEntryDates();
      } catch (e) {
        alert('削除に失敗しました。');
      }
    },
    async saveEntries() {
      this.savingEntry = true;
      try {
        const entries = [];
        for (const dept of this.customer.departments) {
          for (const product of dept.products) {
            entries.push({
              delivery_product_id: product.id,
              date: this.selectedDate,
              quantity: this.quantities[product.id] || 0,
            });
          }
        }
        await axios.post('/api/delivery/entries', { entries }, {
          headers: { Authorization: `Bearer ${this.token}` },
          withCredentials: true,
        });
        await this.fetchEntryDates();
        this.selectedDate = null;
      } catch (e) {
        alert('保存に失敗しました。');
      } finally {
        this.savingEntry = false;
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
