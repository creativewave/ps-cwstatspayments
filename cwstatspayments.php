<?php

require_once _PS_ROOT_DIR_.'/vendor/autoload.php';

class CWStatsPayments extends ModuleGrid
{
    /**
     * Registered hooks.
     *
     * @var array
     */
    const HOOKS = ['displayAdminStatsModules'];

    /**
     * @see ModuleCore
     */
    public $name    = 'cwstatspayments';
    public $tab     = 'analytics_stats';
    public $version = '1.0.0';
    public $author  = 'Creative Wave';
    public $bootstrap = true;
    public $ps_versions_compliancy = [
        'min' => '1.6',
        'max' => '1.6.99.99',
    ];

    /**
     * Initialize module.
     */
    public function __construct()
    {
        parent::__construct();

        $this->displayName      = $this->l('Stats Payments');
        $this->description      = $this->l('Add payments stats to the stats dashboard.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    /**
     * Install module.
     */
    public function install(): bool
    {
        return parent::install() and $this->addHooks(static::HOOKS);
    }

    /**
     * Display or donwload stats.
     */
    public function hookDisplayAdminStatsModules(array $params): string
    {
        $data_params = $this->getDataParams();
        if ($this->isActionAdminExport()) {
            return $this->csvExport($data_params);
        }

        $table = $this->engine($data_params);
        $href  = $this->getContextUri().'&export=1';

        return "
            <div class=\"panel-heading\">$this->displayName</div>
            $table
            <div>
                <a class=\"btn btn-default\" href=\"$href\">
                    <i class=\"icon-cloud-upload\"></i> ".$this->l('CSV Export').'
                </a>
            </div>
        ';
    }

    /**
     * Add hooks.
     */
    protected function addHooks(array $hooks): bool
    {
        return array_product(array_map([$this, 'registerHook'], $hooks));
    }

    /**
     * Get data.
     */
    protected function getData()
    {
        $query = $this->getDbQuery()
            ->select("
                DISTINCT DATE(op.date_add) as date_add,
                    o.id_order,
                    s.name,
                    op.payment_method,
                    CONCAT(op.amount, ' ', c.sign) as amount
            ")
            ->from('order_payment', 'op')
            ->naturalJoin('currency', 'c')
            ->leftJoin('orders', 'o', 'o.reference = op.order_reference')
            ->leftJoin('shop', 's', 'o.id_shop = s.id_shop')
            ->where("$this->_sort BETWEEN ".$this->getDate())
            ->orderBy("op.date_add $this->_direction");

        $this->isContextShopGroupSharingOrders()
            ? $query->where('o.id_shop = '.$this->getContextShopId())
            : $query->where('o.id_shop IN ('.implode(',', $this->getContextShopsIds()));

        if ($this->_start) {
            $query->limit($this->_limit, $this->_start);
        }

        $this->_values = $this->getDb(true)->executes($query);
        $this->_totalCount = $this->getDb(true)->getValue('SELECT FOUND_ROWS()');
    }

    /**
     * Get data params.
     */
    protected function getDataParams(): array
    {
        $params = [
            'columns'              => [],
            'defaultSortColumn'    => 'op.date_add',
            'defaultSortDirection' => 'DESC',
            'pagingMessage'        => sprintf($this->l('Displaying %1$s of %2$s'), '{0} - {1}', '{2}'),
        ];
        $params['columns'][] = [
            'header'    => $this->l('Date'),
            'dataIndex' => 'date_add',
            'align'     => 'center',
        ];
        if (!$this->isContextShopGroupSharingOrders()) {
            $params['columns'][] = [
                'header'    => $this->l('Shop'),
                'dataIndex' => 'name',
                'align'     => 'center',
            ];
        }
        $params['columns'][] = [
            'header'    => $this->l('Order ID'),
            'dataIndex' => 'id_order',
            'align'     => 'center',
        ];
        $params['columns'][] = [
            'header'    => $this->l('Method'),
            'dataIndex' => 'payment_method',
            'align'     => 'center',
        ];
        $params['columns'][] = [
            'header'    => $this->l('Amount'),
            'dataIndex' => 'amount',
            'align'     => 'center',
        ];

        return $params;
    }

    /**
     * Get context shop.
     */
    protected function getContextShop(): Shop
    {
        return $this->context->shop;
    }

    /**
     * Get context shop ID.
     */
    protected function getContextShopId(): int
    {
        return $this->context->shop->id;
    }

    /**
     * Get context shops IDs.
     */
    protected function getContextShopsIds(): array
    {
        return Shop::getContextListShopID();
    }

    /**
     * Get context URI.
     */
    protected function getContextUri(): string
    {
        return Tools::safeOutput($_SERVER['REQUEST_URI']);
    }

    /**
     * Get Db.
     */
    protected function getDb(bool $slave = false): Db
    {
        return Db::getInstance($slave ? _PS_USE_SQL_SLAVE_ : $slave);
    }

    /**
     * Get DbQuery.
     */
    protected function getDbQuery(): DbQuery
    {
        return new DbQuery();
    }

    /**
     * Get value from $_GET/$_POST.
     */
    protected function getValue(string $key, string $default = ''): string
    {
        return Tools::getValue($key, $default);
    }

    /**
     * Wether or not an admin export action is currently processing.
     */
    protected function isActionAdminExport(): bool
    {
        return $this->getValue('export');
    }

    /**
     * Wether or not context is shop group sharing orders.
     */
    protected function isContextShopGroupSharingOrders(): bool
    {
        return $this->isMultistoreContext() and $this->getContextShop()->getGroup()->share_order;
    }

    /**
     * Wether or not context is multistore.
     */
    protected function isMultistoreContext(): bool
    {
        return Shop::isFeatureActive() and Shop::CONTEXT_SHOP !== Shop::getContext();
    }
}
