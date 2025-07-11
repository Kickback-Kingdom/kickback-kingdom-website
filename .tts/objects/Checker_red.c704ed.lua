APObjectGUIDS = {"3fca1c","8265aa"}

function returnAllObjectWithinMatArea(position)
    local hits = Physics.cast({
        origin       = position,
        direction    = {0, 1, 0},
        type         = 3,
        size         = {31, 4, 19},
        orientation  = {0, 0, 0},
        max_distance = 0,
        debug        = false
    })

    local objects = {}
    for _, hit in ipairs(hits) do
        if hit.hit_object then
            table.insert(objects, hit.hit_object)
        end
    end
    return objects
end

function untapAP(APObjects)
    for i, GUID in ipairs(APObjectGUIDS) do
        local object = getObjectFromGUID(GUID)

        local rotation = {0,0,0}

        object.setRotationSmooth(rotation, false, false)
    end
end

function untapObjects(objects, untapDirection)
    for i, object in ipairs(objects) do
        local zRotation = object.getRotation().z

        local rotation = {untapDirection[1],untapDirection[2], zRotation}

        object.setRotationSmooth(rotation, false, false)
    end
end

function onButtonPress()
    local center = {x = 36, y = 1, z = -41}

    local objects = returnAllObjectWithinMatArea(center)

    untapObjects(objects,{0,180,0})
    untapAP()
end

function onLoad()

    self.lock()

    self.createButton({
        click_function = "onButtonPress",
        function_owner = self,
        label = "Untap All",
        position = {0, 0.15, 0},
        rotation = {0, 180, 0},
        width = 1600,
        height = 800,
        font_size = 350,
        color = {0.8, 0, 1, 0.8},
        font_color = {0, 0, 0, 1}
    })

    self.setColorTint({0,0,0,0})
    self.setRotation({0,0,0})
    
end
