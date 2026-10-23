<?php

if (! function_exists('pos_url')) {
    /**
     * 選択中の担当者・顧客 (?m=&c=) を引き継いだ URL。
     * $params の 'm' / 'c' で上書きでき、null を渡すとその値を外す。
     * 例: pos_url('customer.list')、pos_url('home', ['c' => null])、pos_url('customer.select', ['id' => 3])
     */
    function pos_url(string $route, array $params = []): string
    {
        $request = request();
        $manager = $request->attributes->get('manager');
        $customer = $request->attributes->get('customer');

        $context = array_filter([
            'm' => $manager?->getKey(),
            'c' => $customer?->getKey(),
        ], fn ($v) => $v !== null);

        $merged = array_filter(array_merge($context, $params), fn ($v) => $v !== null);

        return route($route, $merged);
    }
}
