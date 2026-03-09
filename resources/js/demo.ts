import { mount } from 'svelte';
import DemoPanel from './components/demo/DemoPanel.svelte';

const target = document.getElementById('demo-panel');
const dataEl = document.getElementById('demo-data');

if (target && dataEl) {
    const data = JSON.parse(dataEl.textContent || '{}');
    mount(DemoPanel, { target, props: { patients: data.patients ?? [], users: data.users ?? [] } });
}
