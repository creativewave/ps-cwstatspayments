<?php

use PHPUnit\Framework\TestCase;

class CWStatsPaymentsTest extends TestCase
{
    const REQUIRED_PROPERTIES = [
        'author',
        'confirmUninstall',
        'description',
        'displayName',
        'name',
        'ps_versions_compliancy',
        'tab',
        'version',
    ];
    const REQUIRED_HOOKS = [
        'displayAdminStatsModules',
    ];

    /**
     * New instance should have required properties.
     */
    public function testInstanceHasRequiredProperties()
    {
        $module = new CWStatsPayments();
        foreach (self::REQUIRED_PROPERTIES as $prop) {
            $this->assertNotNull($module->$prop);
        }
    }

    /**
     * CWStatsPayments::install() should add required hooks.
     */
    public function testInstall()
    {
        $mock = $this
            ->getMockBuilder('CWStatsPayments')
            ->setMethods(['addHooks'])
            ->getMock();

        $mock
            ->expects($this->once())
            ->method('addHooks')
            ->with($this->equalTo(self::REQUIRED_HOOKS))
            ->willReturn(true);

        $mock->install();
    }

    /**
     * CWStatsPayments::hookDisplayAdminStatsModules() should download stats if
     * an export action is currently processing.
     */
    public function testDownloadStats()
    {
        $mock = $this
            ->getMockBuilder('CWStatsPayments')
            ->setMethods([
                'csvExport',
                'isActionAdminExport',
            ])
            ->getMock();

        $mock->method('isActionAdminExport')->willReturn(true);

        $mock
            ->expects($this->once())
            ->method('csvExport')
            ->willReturn('');

        $mock->hookDisplayAdminStatsModules([]);
    }

    /**
     * CWStatsPayments::hookDisplayAdminStatsModules() should display stats.
     */
    public function testDisplayStats()
    {
        $mock = $this
            ->getMockBuilder('CWStatsPayments')
            ->setMethods([
                'engine',
                'getContextUri',
                'isActionAdminExport',
            ])
            ->getMock();

        $mock->method('engine')->willReturn('data');
        $mock->method('getContextUri')->willReturn('uri');
        $mock->method('isActionAdminExport')->willReturn(false);

        $this->assertSame(
            $mock->hookDisplayAdminStatsModules([]),
        "
            <div class=\"panel-heading\">$mock->displayName</div>
            data
            <div>
                <a class=\"btn btn-default\" href=\"uri&export=1\">
                    <i class=\"icon-cloud-upload\"></i> CSV Export
                </a>
            </div>
        "
        );
    }
}
