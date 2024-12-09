<?php


    function conectar(){
        $cnn = new mysqli("localhost","root","@Canelo67","publimpacto");
        return $cnn;
    }
?>