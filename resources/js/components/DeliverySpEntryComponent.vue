<template>
  <div class="sp-entry" style="padding: 12px;">

    <!-- 入力状況一覧 -->
    <div v-if="!selectedDate">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="mb-0">入力状況</h5>
        <button class="btn btn-primary" style="font-size:18px; padding: 10px 20px;" @click="selectDate(today)">
          今日 ({{ today }}) を入力
        </button>
      </div>

      <!-- 顧客選択（複数社ある場合） -->
      <div v-if="customers.length > 1" class="mb-3">
        <div v-for="c in customers" :key="c.id">
          <button class="btn btn-outline-primary w-100 mb-2"
            :class="{ 'btn-primary text-white': selectedCustomer && selectedCustomer.id === c.id }"
            @click="selectedCustomer = c">
            {{ c.name }}
          </button>
        </div>
      </div>

      <!-- 入力済み日付一覧 -->
      <div v-if="selectedCustomer">
        <div v-if="loadingDates" class="text-center py-3">読み込み中...</div>
        <div v-else>
          <div v-for="d in entryDates" :key="d.date"
               class="card mb-2"
               @click="selectDate(d.date)"
               style="cursor:pointer; padding: 14px 18px;">
            <div class="d-flex align-items-center justify-content-between">
              <span style="font-size: 18px;">{{ formatDate(d.date) }}</span>
              <span v-if="d.finalized" style="font-size: 20px; color: #1976D2;">✅ 確定済</span>
              <span v-else style="font-size: 20px; color: #388E3C;">✅ 入力済</span>
            </div>
          </div>
          <div v-if="entryDates.length === 0" class="text-muted text-center py-3">入力済みデータはありません</div>
        </div>
      </div>
    </div>

    <!-- 日次入力フォーム -->
    <div v-else>
      <div class="d-flex align-items-center mb-3">
        <button class="btn btn-outline-secondary me-3" @click="selectedDate = null">← 戻る</button>
        <h5 class="mb-0">{{ formatDate(selectedDate) }} の記帳</h5>
      </div>

      <div v-if="loading" class="text-center py-3">読み込み中...</div>
      <div v-else>
        <div v-for="dept in selectedCustomer.departments" :key="dept.id" class="mb-4">
          <div class="card-header bg-secondary text-white px-3 py-2 mb-1" style="border-radius: 8px; font-size: 16px; font-weight: bold;">
            {{ dept.name }}
          </div>
          <div v-for="product in dept.products" :key="product.id" class="card mb-2 p-3">
            <div class="d-flex align-items-center justify-content-between">
              <span style="font-size: 16px; flex: 1;">{{ product.name }}</span>
              <div class="d-flex align-items-center" style="gap: 8px;">
                <button class="btn btn-outline-secondary"
                  style="width: 48px; height: 48px; font-size: 22px;"
                  @click="adjust(product.id, -1)">－</button>
                <input
                  type="text"
                  inputmode="numeric"
                  pattern="[0-9]*"
                  :value="quantities[product.id] || 0"
                  @focus="$event.target.select()"
                  @change="setQuantity(product.id, $event.target.value)"
                  style="width: 72px; height: 48px; font-size: 22px; text-align: center; border: 2px solid #ccc; border-radius: 8px;">
                <button class="btn btn-outline-secondary"
                  style="width: 48px; height: 48px; font-size: 22px;"
                  @click="adjust(product.id, 1)">＋</button>
              </div>
            </div>
          </div>
        </div>

        <div style="position: sticky; bottom: 0; background: #fff; padding: 12px 0;">
          <button class="btn btn-success w-100"
            style="font-size: 20px; padding: 18px;"
            :disabled="saving"
            @click="save">
            {{ saving ? '保存中...' : '保存する' }}
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
export default {
  props: {
    customers: { type: Array, required: true },
    token: { type: String, required: true },
  },
  data() {
    return {
      selectedCustomer: this.customers.length === 1 ? this.customers[0] : null,
      selectedDate: null,
      quantities: {},
      entryDates: [],
      loadingDates: false,
      loading: false,
      saving: false,
      today: new Date().toISOString().slice(0, 10),
    };
  },
  watch: {
    selectedCustomer(val) {
      if (val) this.fetchEntryDates();
    },
    selectedDate(val) {
      if (val) this.fetchEntries();
    },
  },
  mounted() {
    if (this.selectedCustomer) this.fetchEntryDates();
  },
  methods: {
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
          params: { customer_id: this.selectedCustomer.id },
          headers: { Authorization: `Bearer ${this.token}` },
          withCredentials: true,
        });
        this.entryDates = res.data.dates.sort((a, b) => b.date.localeCompare(a.date));
      } finally {
        this.loadingDates = false;
      }
    },
    async fetchEntries() {
      this.loading = true;
      this.quantities = {};
      try {
        const res = await axios.get('/api/delivery/entries', {
          params: { customer_id: this.selectedCustomer.id, date: this.selectedDate },
          headers: { Authorization: `Bearer ${this.token}` },
          withCredentials: true,
        });
        res.data.entries.forEach(e => {
          this.$set(this.quantities, e.delivery_product_id, e.quantity);
        });
      } finally {
        this.loading = false;
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
    async save() {
      this.saving = true;
      try {
        const entries = [];
        for (const dept of this.selectedCustomer.departments) {
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
        alert('保存に失敗しました。もう一度お試しください。');
      } finally {
        this.saving = false;
      }
    },
  },
};
</script>
