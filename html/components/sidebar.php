<div class="sidebar-wrapper" >
    <div>
        <nav class="sidebar-main">
            <div class="left-arrow" id="left-arrow"><i data-feather="arrow-left"></i></div>
            <div id="sidebar-menu">
                <ul class="sidebar-links" id="simple-bar">
                    <li class="back-btn">
                        <div class="mobile-back text-end">
                            <span>Back</span>
                            <i class="fa fa-angle-right ps-2" aria-hidden="true"></i>
                        </div>
                    </li>
                    <li class="sidebar-list">
                        <a class="sidebar-link sidebar-title" href="../">
                            <i class="icofont icofont-castle" style="font-size: 2em;">
                            </i>
                            <span> Home</span>
                        </a>
                    </li>
                    <li class="sidebar-list">
                        <a class="sidebar-link sidebar-title" href="/town-square.php">
                            <i class="icofont icofont-users-social" style="font-size: 2em;">
                            </i>
                            <span> Town Square</span>
                        </a>
                    </li>
                    <li class="sidebar-list">
                        <a class="sidebar-link sidebar-title" href="#">
                            <span>
                                <i class="icofont icofont-direction-sign" style="font-size: 2em;">
                                </i> Guild Halls
                            </span>
                        </a>
                        <ul class="sidebar-submenu">
                            <li class="sidebar-list">
                                <a class="sidebar-link sidebar-title" href="../adventurers-guild.php"
                                    style="padding-left: 15px;">
                                    <i class="icofont icofont-tracking" style="font-size: 2em;">
                                    </i>
                                    <span> Adventurers Guild</span>
                                </a>
                            </li>
                            <li class="sidebar-list">
                                <a class="sidebar-link sidebar-title" href="../coming-soon.php"
                                    style="padding-left: 15px;">
                                    <i class="icofont icofont-coins" style="font-size: 2em;">
                                    </i>
                                    <span> Merchants Guild</span>
                                </a>
                            </li>
                            <li class="sidebar-list">
                                <a class="sidebar-link sidebar-title" href="../coming-soon.php"
                                    style="padding-left: 15px;">
                                    <i class="icofont icofont-worker" style="font-size: 2em;">
                                    </i>
                                    <span> Craftsmen Guild</span>
                                </a>
                            </li>
                            <li class="sidebar-list">
                                <a class="sidebar-link sidebar-title" href="../coming-soon.php"
                                    style="padding-left: 15px;">
                                    <i class="icofont icofont-hat" style="font-size: 2em;">
                                    </i>
                                    <span> Apprentices Guild</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php
                    if (IsAdmin())
                    {

                    
                    ?>
                    <li class="sidebar-list">
                        <a class="sidebar-link sidebar-title" href="../coming-soon.php">
                            <i class="icofont icofont-shield" style="font-size: 2em;">
                            </i>
                            <span> Admin</span>
                        </a>
                    </li>
                    <?php
                    }
                    ?>
                </ul>

            </div>
        </nav>
    </div>
</div>