<!DOCTYPE html>
<html>

<head>
    <title>Pythagoras AI Oracle</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    



</head>



<body class="bg-purple-900 text-slate-200 linear-gradient(to right, rgb(15, 23, 42), rgb(88, 28, 135), rgb(15, 23, 42))">
    

<div class="flex justify-center items-center xxh-screen w-screen">

    <?php show_bganim(1); ?>


    <?php // echo "<pre>"; var_dump($_SESSION); echo "</pre>"; 
    ?>
    <?php if (isset($_SESSION['user'])) { ?>
        <div class="bg-purple-400 text-white fixed top-0 left-0 right-0 z-50">
            <div class="container mx-auto flex justify-between items-center py-1">
                <div class="flex items-center">
                    <img src="<?php echo $_SESSION['user']['profile_pic']; ?>" alt="Profile Picture" class="w-8 h-8 rounded-full mr-2">
                    <span class="mr-4"><?php echo $_SESSION['user']['email']; ?></span>
                    <span class="mr-4">Available Credits: <?php echo $_SESSION['user']['credits']; ?></span>
                </div>
                <a href="<?php echo $gc['path']['web_root']; ?>/logout" class="px-4 py-1 hover:text-slate-800 underlined">Logout</a>
            </div>
        </div>

    <?php } ?>



<script>
                /*
                //<div id="bg-nums" class="bg-nums"></div>
                $(document).ready(function() {
                    // Generate 20 divs with random numbers
                    for (let i = 0; i < 20; i++) {
                        let randomNumber = Math.floor(Math.random() * 9) + 1;
                        let randomSize = Math.floor(Math.random() * 11) + 10;
                        let randomRotation = Math.floor(Math.random() * 361);
                        let randomPosition = Math.floor(Math.random() * ($(window).width() - 100));
                        let randomDirection = Math.random() < 0.5 ? -1 : 1;
                        let randomDelay = Math.floor(Math.random() * 8000) + 3000;

                        let div = $('<div>').text(randomNumber)
                            .css({
                                'position': 'absolute',
                                'top': Math.floor(Math.random() * $(window).height()),
                                'left': randomPosition,
                                'font-size': randomSize + 'px',
                                'transform': 'rotate(' + randomRotation + 'deg)',
                                'opacity': 1
                            })
                            .appendTo('#bg-nums')
                            .delay(randomDelay)
                            .animate({
                                'top': '+=' + (Math.random() * 100 * randomDirection) + 'px',
                                'left': '+=' + (Math.random() * 100 * randomDirection) + 'px',
                                'opacity': 0,
                                'transform': 'rotate(' + (randomRotation + 360) + 'deg)' // Add slow rotation
                            }, 5000, function() {
                                $(this).css({
                                    'top': Math.floor(Math.random() * $(window).height()),
                                    'left': Math.floor(Math.random() * ($(window).width() - 100)),
                                    'opacity': 1,
                                    'transform': 'rotate(' + randomRotation + 'deg)' // Reset rotation
                                });
                                $(this).delay(Math.floor(Math.random() * 8000) + 3000).animate({
                                    'top': '+=' + (Math.random() * 100 * randomDirection) + 'px',
                                    'left': '+=' + (Math.random() * 100 * randomDirection) + 'px',
                                    'opacity': 0,
                                    'transform': 'rotate(' + (randomRotation + 360) + 'deg)' // Add slow rotation
                                }, 5000, arguments.callee);
                            });
                    }
                });

                */
            </script>


