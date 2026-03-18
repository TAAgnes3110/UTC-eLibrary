import { reactive } from 'vue';

let _id = 1;

export const toastState = reactive({
    items: [],
});

function add(type, message, opts = {}) {
    const id = _id++;
    const duration = typeof opts.duration === 'number' ? opts.duration : 3500;
    const title = opts.title ?? null;

    toastState.items.push({ id, type, title, message });

    if (duration > 0) {
        window.setTimeout(() => remove(id), duration);
    }
    return id;
}

export function remove(id) {
    const idx = toastState.items.findIndex((t) => t.id === id);
    if (idx >= 0) toastState.items.splice(idx, 1);
}

export const toast = {
    success(message, opts = {}) {
        return add('success', message, opts);
    },
    error(message, opts = {}) {
        return add('error', message, { duration: 6000, ...opts });
    },
    info(message, opts = {}) {
        return add('info', message, opts);
    },
    warn(message, opts = {}) {
        return add('warn', message, { duration: 5000, ...opts });
    },
};

