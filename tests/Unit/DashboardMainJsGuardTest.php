<?php

uses(Tests\TestCase::class);

/**
 * BUG-UI-002 — `main.js` dimuat di semua halaman, sedangkan TinyMCE hanya
 * dimuat pada halaman tertentu. Tanpa guard, `tinymce.init` melempar
 * ReferenceError dan menghentikan sisi lain script.
 *
 * Tes ini menjalankan main.js di Node dengan DOM tiruan:
 *  - tanpa TinyMCE  → tidak boleh ada error,
 *  - dengan TinyMCE → init tetap dipanggil sekali (fitur tidak rusak).
 */
function mainJsHarnessPath(): string
{
    static $path = null;

    if ($path !== null) {
        return $path;
    }

    $harness = <<<'JS'
const fs = require('fs');
const vm = require('vm');

const sourcePath = process.argv[2];
const withTinyMce = process.argv[3] === 'with-tinymce';
const code = fs.readFileSync(sourcePath, 'utf8');

function fakeElement(tag = 'div') {
  return {
    tagName: tag,
    classList: { add() {}, remove() {}, toggle() {} },
    style: {},
    offsetTop: 0,
    offsetHeight: 0,
    addEventListener() {},
    appendChild() {},
    contains() { return false; },
    querySelector() { return null; },
    querySelectorAll() { return []; },
  };
}

const tinymceTextarea = fakeElement('textarea');
let tinymceInitCalls = 0;

const document = {
  body: fakeElement('body'),
  querySelector(selector) {
    if (withTinyMce && String(selector).includes('tinymce-editor')) {
      return tinymceTextarea;
    }
    return null;
  },
  querySelectorAll() { return []; },
  getElementById() { return null; },
  addEventListener() {},
};

const window = {
  scrollY: 0,
  matchMedia() { return { matches: false }; },
  addEventListener() {},
};

const sandbox = {
  window,
  document,
  console,
  setTimeout(fn) { fn(); },
  ResizeObserver: function () { this.observe = () => {}; },
  bootstrap: { Tooltip: function () {} },
  simpleDatatables: { DataTable: function () {} },
  echarts: { getInstanceByDom() { return { resize() {} }; } },
};

if (withTinyMce) {
  sandbox.tinymce = {
    init() { tinymceInitCalls += 1; },
  };
}

try {
  vm.runInNewContext(code, sandbox, { filename: 'main.js' });
} catch (error) {
  console.error('THROW: ' + error.message);
  process.exit(1);
}

if (withTinyMce && tinymceInitCalls !== 1) {
  console.error('tinymce.init expected once, got ' + tinymceInitCalls);
  process.exit(1);
}
JS;

    $path = tempnam(sys_get_temp_dir(), 'mainjs-harness-').'.js';
    file_put_contents($path, $harness);

    return $path;
}

function mainJsRun(string $mode = ''): array
{
    $node = trim((string) shell_exec('command -v node 2>/dev/null'));

    if ($node === '') {
        return ['skipped' => true];
    }

    $script = public_path('DashboardTemplates/assets/js/main.js');
    $cmd = escapeshellarg($node)
        .' '.escapeshellarg(mainJsHarnessPath())
        .' '.escapeshellarg($script)
        .' '.escapeshellarg($mode)
        .' 2>&1';

    exec($cmd, $output, $exitCode);

    return [
        'skipped' => false,
        'exit'    => $exitCode,
        'output'  => implode("\n", $output),
    ];
}

it('memuat main.js tanpa error saat TinyMCE tidak tersedia', function () {
    $result = mainJsRun();

    if ($result['skipped']) {
        $this->markTestSkipped('Node.js tidak tersedia untuk menjalankan main.js.');
    }

    expect($result['exit'])->toBe(0, $result['output']);
});

it('tetap menginisialisasi TinyMCE saat library dan textarea tersedia', function () {
    $result = mainJsRun('with-tinymce');

    if ($result['skipped']) {
        $this->markTestSkipped('Node.js tidak tersedia untuk menjalankan main.js.');
    }

    expect($result['exit'])->toBe(0, $result['output']);
});

it('menjaga tinymce.init dengan pengecekan library dan elemen DOM', function () {
    $source = file_get_contents(public_path('DashboardTemplates/assets/js/main.js'));

    expect($source)->toMatch('/typeof\s+tinymce\s*!==\s*[\'"]undefined[\'"]/')
        ->and($source)->toContain("select('textarea.tinymce-editor')");
});
