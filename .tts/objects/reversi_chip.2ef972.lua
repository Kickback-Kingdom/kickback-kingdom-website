function onButtonPress()
    local rotation = {self.getRotation().x + 180, self.getRotation().y, self.getRotation().z}

    self.setRotationSmooth(rotation, false, true)
end

function onLoad()

    self.lock()

    self.createButton({
        click_function = "onButtonPress",
        function_owner = self,
        label = "",
        position = {0, 0.15, 0},
        rotation = {0, 0, 0},
        width = 800,
        height = 800,
        font_size = 300,
        color = {1, 0, 0, 0},
        font_color = {1, 1, 1, 1}
    })

    self.createButton({
        click_function = "onButtonPress",
        function_owner = self,
        label = "",
        position = {0, -0.15, 0},
        rotation = {180, 0, 0},
        width = 800,
        height = 800,
        font_size = 300,
        color = {1, 0, 0, 0},
        font_color = {1, 1, 1, 1}
    })

    
end