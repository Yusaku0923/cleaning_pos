<template
    v-for="manager in managers"
    :key="manager.id">
    <div class="form-check mb-4">
        <input class="form-check-input col-2" type="radio" name="id" >
        <label class="form-check-label col-10" for="manager_">
            <div class="card h3 p-3 text-right">{{ manager.name }}</div>
        </label>
    </div>
</template>

<script>
export default ({
    props: {
        managers: {
            type: Array,
            required: true
        },
    },
    
    methods: {

    }
});
</script>
