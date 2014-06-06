<?php
$this->perfsInfo = $this->app()->get('perfs')->getAllPerfsInfo();
?>

<?= $this->hooks()->html('perfs_info') ?>
<div id="perfs-info">
    <fieldset>
        <legend>App performance</legend>
        <ul>
            <li>
                For URL: <code class="current-action-url"><?= $this->e($this->utils()->getCurrentPath()) ?></code>
            </li>
            <li>
                Number of SQL queries: <b class="nb-sql-queries"><?= $this->perfsInfo['nbSqlQueries'] ?></b>
            </li>
            <li>
                Total number of plugins: <b class="nb-plugins"><?= $this->perfsInfo['nbPlugins'] ?></b>
            </li>
            <li>
                Time elapsed until this display: <b class="perfs-elapsed-time-now"><?= $this->perfsInfo['elapsedTimeNow'] ?></b>s.<br>
                - time elapsed at bootstrap (before plugins initialization): <b class="perfs-elapsed-time-bootstrap"><?= $this->perfsInfo['elapsedTimeAtBootstrap'] ?></b>s.<br>
                - time elapsed at "app ready" (after Plugins init, just before <code>$app->run()</code>): <b class="perfs-elapsed-time-plugins-init"><?= $this->perfsInfo['elapsedTimeAtPluginsInit'] ?></b>s.
            </li>
            <li class="hidden">
                {#
                We can't display this data, since QueryPath hooks run *after* the page content has been rendered.
                --> but we will display it when we will receive the data from Ajax Responses HTTP headers!
                #}
                QueryPath phase duration: <b class="query-path-duration">N/A</b>s.
            </li>
            <li>
                Included files: <b class="perfs-nb-included-files-now"><?= $this->perfsInfo['nbIncludedFilesNow'] ?></b><br>
                - at bootstrap phase: <b class="perfs-nb-included-files-bootstrap"><?= $this->perfsInfo['nbIncludedFilesAtBootstrap'] ?></b><br>
                - at "app ready" phase : <b class="perfs-nb-included-files-plugins-init"><?= $this->perfsInfo['nbIncludedFilesAtPluginsInit'] ?></b>
            </li>
            <li>
                Permanently disabled plugins: <b class="nb-plugins-permanently-disabled"><?= $this->perfsInfo['nbPluginsPermanentlyDisabled'] ?></b>
            </li>
            <li>
                Plugins disabled for current URL: <b class="nb-plugins-disabled-for-current-url"><?= $this->perfsInfo['nbPluginsDisabledForCurrentUrl'] ?></b>
            </li>
            <li>
                Actions registered: <b class="nb-actions-registered"><?= $this->perfsInfo['nbActionsRegistered'] ?></b>
            </li>
            <?php if (isset($this->perfsInfo['sqlQueries'])): ?>
            <li>
                <span class="nb-sql-queries"><?= $this->perfsInfo['nbSqlQueries'] ?></span> SQL queries:
                <ul class="sql-queries">
                    <?php foreach($this->perfsInfo['sqlQueries'] as $query): ?>
                    <li>
                        <b><?= $query['time'] ?></b>ms. :
                        <i><?= $this->e($query['query']) ?></i>
                        - bindings: <i><?= $this->e(json_encode($query['bindings'])) ?></i>
                    </li>
                    <?php endforeach ?>
                </ul>
            </li>
            <?php endif ?>
            <li>
                Session content: <pre class="session-content"><?= $this->e(json_encode($this->app()->get('session')->all())) ?></pre>
            </li>
        </ul>
    </fieldset>
</div>