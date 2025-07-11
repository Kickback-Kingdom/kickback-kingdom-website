wallObjects = {}
sourceStoneObjects = {}
sourceStoneButtonIndexMap = {}
buttonIndexMap = {}
wallsLocked = true

function onLoad()
    --spawnBoardObject("sourceStone")
    createWallButtons()
    --createSourceStoneButtons()
end

function registerToBoard(params)
    local object = params[1]
    local objectType = params[2]

    if(objectType == "wall")then
        table.insert(wallObjects, object)
    elseif(objectType == "sourceStone")then
        table.insert(sourceStoneObjects, object)
        refreshSourceStoneButtons() 
    else
        print("object type not found! type : "..objectType)
    end
    
end

function createButton(buttonName, buttonInfo, buttonType)

    self.createButton(buttonInfo)

    local currentIndex = self.getButtons() and #self.getButtons() - 1 or 0

    table.insert(buttonIndexMap, {
        name = buttonName,
        object = self,
        index = currentIndex,
        type = buttonType
    })

    
end

--source-stones---------------------------------------------------------

function createSourceStoneButtons()

    for i, stone in ipairs(sourceStoneObjects) do


        --[[local position = {stone.getPosition().x, stone.getPosition().y, stone.getPosition().z}
        local rotation = stone.getRotation()

        local buttonInfo = {
            click_function = "toggleLockWalls",
            function_owner = stone,
            label = "Unlock Walls",
            position = position,
            rotation = rotation,
            width = 1600,
            height = 500,
            font_size = 250,
            color = {1, 0, 0, 1},
            font_color = {0, 0, 0, 1}
        }
        

        createButton("sourceStoneButton"..i, buttonInfo, "sourceStoneButton")--]]
    end
end

function refreshSourceStoneButtons() 
    for i, button in ipairs(sourceStoneButtonIndexMap) do
        self.removeButton(button.index)
    end

    createSourceStoneButtons()
end

--Walls-----------------------------------------------------------------

function createWallButtons()
    local buttons = {
        {name="WallButton1", position={20, 0.5, 0}, rotation={0, -30, 0}},
        {name="WallButton2", position={-20, 0.5, 0}, rotation={0, 150, 0}},
        {name="WallButton3", position={9, 0.5, -18}, rotation={0, 150, 0}},
        {name="WallButton4", position={-9, 0.5, 18}, rotation={0, -30, 0}},
    }

    for i, btn in ipairs(buttons) do
        local buttonInfo = {
            click_function = "toggleLockWalls",
            function_owner = self,
            label = "Unlock Walls",
            position = btn.position,
            rotation = btn.rotation,
            width = 1600,
            height = 500,
            font_size = 250,
            color = {1, 0, 0, 1},
            font_color = {0, 0, 0, 1}
        }
        createButton(btn.name, buttonInfo, "wallButton")
    end

end

function toggleLockWalls(obj, player_color)
    local buttonColor = {1, 0, 0, 1}
    local labelString = "Unlock Walls"

    if (wallsLocked) then
        buttonColor = {0, 1, 0, 1}
        wallsLocked = false
        labelString = "Lock Walls"
    elseif (wallsLocked == false) then
        wallsLocked = true
    end

    local wallButtons = returnAllWallButtons()

    for _, btn in ipairs(wallButtons) do
        btn.object.editButton({
            index = btn.index,
            color = buttonColor,
            label = labelString
        })
    end

    for i, wall in ipairs(wallObjects) do
        if(wallsLocked == true) then
            wall.lock()
        elseif (wallsLocked == false) then
            wall.unlock()
        end
    end
end

--spawn-objects---------------------------------------------------------

function spawnBoardObject(objectName)

    local modelData = returnModelData(objectName)

    if(modelData.objectType == "customModel") then
        local objectData = {
            type = "Custom_Model",
            position = {0, 2, 0},
            callback_function = function(obj)
                obj.setCustomObject(modelData)
                obj.setLuaScript(modelData.lua_script)
                obj.setName("Cool 3D Object")
                obj.reload()
            end
        }
    
        spawnObject(objectData)
    elseif(modelData.objectType == "jsonClone") then
        local objectData = {
            json = modelData.jsonData,
            position = {0, 2, 0},
            callback_function = function(obj)
                log("Spawned saved object:")
                obj.setLuaScript(modelData.lua_script)
                obj.setName("Cool 3D Object")
                obj.reload()
            end
        }

        spawnObjectJSON(objectData)
    else
        print("objectType not recognized! type : "..modelData.objectType)
    end
   
end

function returnModelData(objectName)

    if(objectName == "stonewall1") then
        return 
        {
            objectType = "customModel",
            mesh = "https://steamusercontent-a.akamaihd.net/ugc/61457201444625903/85D6BAC104A484A17DD70710A580171BFBDB72DB/",
            collider = "https://steamusercontent-a.akamaihd.net/ugc/61457201444625903/85D6BAC104A484A17DD70710A580171BFBDB72DB/",
            diffuse = "https://steamusercontent-a.akamaihd.net/ugc/61457201444626681/6AA3AED17ABFC1837BAB4A618B87F13AC6978D3D/",
            type = 4,
            material = 1 ,
            lua_script = returnWallLuaScript()
        }
    elseif(objectName == "stonewall2")then
        return 
        {
            objectType = "customModel",
            mesh = "http://example.com/model.obj",
            collider = "http://example.com/collider.obj",
            diffuse = "http://example.com/texture.png",
            type = 4,
            material = 1,
            lua_script = returnWallLuaScript()
        }
    elseif(objectName == "cliff")then
        return 
        {
            objectType = "customModel",
            mesh = "http://example.com/model.obj",
            collider = "http://example.com/collider.obj",
            diffuse = "http://example.com/texture.png",
            type = 4,
            material = 1,
            lua_script = returnWallLuaScript()
        }
    elseif(objectName == "cage")then
        return 
        {
            objectType = "customModel",
            mesh = "http://example.com/model.obj",
            collider = "http://example.com/collider.obj",
            diffuse = "http://example.com/texture.png",
            type = 4,
            material = 1,
            lua_script = returnWallLuaScript()
        }
    elseif(objectName == "throne")then
        return 
        {
            objectType = "customModel",
            mesh = "http://example.com/model.obj",
            collider = "http://example.com/collider.obj",
            diffuse = "http://example.com/texture.png",
            type = 4,
            material = 1,
            lua_script = returnWallLuaScript()
        }
    elseif(objectName == "crystal")then
        return 
        {
            objectType = "customModel",
            mesh = "http://example.com/model.obj",
            collider = "http://example.com/collider.obj",
            diffuse = "http://example.com/texture.png",
            type = 4,
            material = 1,
            lua_script = returnWallLuaScript()
        }
    elseif(objectName == "crystal")then
        return 
        {
            objectType = "customModel",
            mesh = "http://example.com/model.obj",
            collider = "http://example.com/collider.obj",
            diffuse = "http://example.com/texture.png",
            type = 4,
            material = 1,
            lua_script = returnWallLuaScript()
        }
    elseif(objectName == "sourceStone")then
        return 
        {
            objectType = "jsonClone",
            jsonData= returnSourceStoneJSON(),
            lua_script = returnSourceStoneLuaScript()
        }
    else
        print("Object name not recognized! objectName : "..objectName)
    end
end

function returnWallLuaScript()
    return[[
    boardGUID = "068885"

    function onLoad()
    local board = getObjectFromGUID(boardGUID)

    board.call("registerToBoard", { self, "wall" })

    end
    ]]
end

function returnSourceStoneLuaScript()
    return[[
    boardGUID = "068885"

    function onLoad()
    local board = getObjectFromGUID(boardGUID)

    board.call("registerToBoard", { self, "wall" })

    end
    ]]
end

function returnSourceStoneJSON()
    return [[{
  "Name": "Tileset_Rock",
  "Transform": {
    "posX": -55.9215775,
    "posY": 2.49286842,
    "posZ": 18.04506,
    "rotX": -0.000147691637,
    "rotY": 270.0003,
    "rotZ": -4.9193266E-05,
    "scaleX": 0.350000441,
    "scaleY": 0.350000381,
    "scaleZ": 0.350000441
  },
  "ColorDiffuse": {
    "r": 0.0,
    "g": 0.0,
    "b": 0.0
  },
  "ChildObjects": [
    {
      "Name": "Tileset_Rock",
      "Transform": {
        "posX": -5.77858734,
        "posY": 4.01388178E-07,
        "posZ": 0.0654185638,
        "rotX": -5.9467834E-06,
        "rotY": -0.00370879285,
        "rotZ": -4.591316E-06,
        "scaleX": 0.99999994,
        "scaleY": 1.0,
        "scaleZ": 0.99999994
      },
      "ColorDiffuse": {
        "r": 0.0,
        "g": 0.0,
        "b": 0.0
      }
    },
    {
      "Name": "Tileset_Rock",
      "Transform": {
        "posX": -3.32014251,
        "posY": -2.179134E-07,
        "posZ": 1.4096694,
        "rotX": -7.620081E-06,
        "rotY": 270.0001,
        "rotZ": 3.17636136E-07,
        "scaleX": 0.9999997,
        "scaleY": 1.0,
        "scaleZ": 0.9999997
      },
      "AltLookAngle": {
        "x": 0.0,
        "y": 0.0,
        "z": 0.0
      },
      "ColorDiffuse": {
        "r": 0.7960787,
        "g": 0.0,
        "b": 1.0
      }
    },
    {
      "Name": "Tileset_Rock",
      "Transform": {
        "posX": -3.55918121,
        "posY": 4.65619223E-07,
        "posZ": -0.551612,
        "rotX": -5.55689257E-06,
        "rotY": 89.99566,
        "rotZ": -1.79230883E-05,
        "scaleX": 0.9999997,
        "scaleY": 1.0,
        "scaleZ": 0.9999997
      },
      "ColorDiffuse": {
        "r": 1.0,
        "g": 0.0,
        "b": 0.0
      }
    },
    {
      "Name": "Tileset_Rock",
      "Transform": {
        "posX": -5.074722,
        "posY": 5.929691E-07,
        "posZ": -1.910814,
        "rotX": 1.34220782E-05,
        "rotY": 179.999741,
        "rotZ": 1.54189365E-07,
        "scaleX": 0.9999999,
        "scaleY": 1.0,
        "scaleZ": 0.9999999
      },
      "ColorDiffuse": {
        "r": 1.0,
        "g": 1.0,
        "b": 1.0
      }
    },
    {
      "Name": "Tileset_Rock",
      "Transform": {
        "posX": -1.54079914,
        "posY": 1.90329757E-07,
        "posZ": 1.18533659,
        "rotX": -1.01766809E-05,
        "rotY": -3.07358532E-05,
        "rotZ": -5.927769E-07,
        "scaleX": 0.9999999,
        "scaleY": 1.0,
        "scaleZ": 0.9999999
      },
      "ColorDiffuse": {
        "r": 0.0,
        "g": 1.0,
        "b": 0.360784262
      }
    },
    {
      "Name": "Tileset_Rock",
      "Transform": {
        "posX": -1.70111012,
        "posY": 5.01684553E-07,
        "posZ": -1.20144808,
        "rotX": 1.08856327E-07,
        "rotY": 0.000368830224,
        "rotZ": -7.506163E-06,
        "scaleX": 0.99999994,
        "scaleY": 1.00000012,
        "scaleZ": 0.99999994
      },
      "AltLookAngle": {
        "x": 0.0,
        "y": 0.0,
        "z": 0.0
      },
      "ColorDiffuse": {
        "r": 0.0,
        "g": 1.0,
        "b": 0.360784233
      }
    },
    {
      "Name": "Tileset_Rock",
      "Transform": {
        "posX": -0.4043977,
        "posY": 3.17160442E-07,
        "posZ": -2.38428473,
        "rotX": 1.04972719E-06,
        "rotY": 89.9991,
        "rotZ": -9.328487E-06,
        "scaleX": 0.9999997,
        "scaleY": 1.0,
        "scaleZ": 0.9999997
      },
      "ColorDiffuse": {
        "r": 1.0,
        "g": 0.0,
        "b": 0.0
      }
    },
    {
      "Name": "Tileset_Rock",
      "Transform": {
        "posX": -4.14332962,
        "posY": 1.89117529E-07,
        "posZ": -3.38780665,
        "rotX": 1.03221E-05,
        "rotY": 269.9988,
        "rotZ": 8.841147E-06,
        "scaleX": 0.99999994,
        "scaleY": 1.0,
        "scaleZ": 0.99999994
      },
      "ColorDiffuse": {
        "r": 0.7960787,
        "g": 0.0,
        "b": 1.0
      }
    },
    {
      "GUID": "f9cbad",
      "Name": "Tileset_Rock",
      "Transform": {
        "posX": -2.21724486,
        "posY": 3.723779E-07,
        "posZ": -3.46654224,
        "rotX": -1.00394418E-05,
        "rotY": 180.0009,
        "rotZ": -4.155268E-06,
        "scaleX": 0.9999999,
        "scaleY": 1.0,
        "scaleZ": 0.9999999
      },
      "Nickname": "",
      "Description": "",
      "GMNotes": "",
      "AltLookAngle": {
        "x": 0.0,
        "y": 0.0,
        "z": 0.0
      },
      "ColorDiffuse": {
        "r": 1.0,
        "g": 1.0,
        "b": 1.0
      }
    },
    {
      "Name": "Custom_Model",
      "Transform": {
        "posX": -2.73127675,
        "posY": -2.27327,
        "posZ": -1.10570455,
        "rotX": 1.66444934E-05,
        "rotY": -0.00360292452,
        "rotZ": -1.07959331E-05,
        "scaleX": 4.33142042,
        "scaleY": 4.331421,
        "scaleZ": 4.33142042
      },
      "ColorDiffuse": {
        "r": 0.0,
        "g": 0.0,
        "b": 0.0,
        "a": 0.605948031
      },
      "CustomMesh": {
        "MeshURL": "https://steamusercontent-a.akamaihd.net/ugc/1050975298009099284/EC12429E233E4188A583AE03C7DB08B60119E1FE/",
        "DiffuseURL": "",
        "NormalURL": "",
        "ColliderURL": "https://steamusercontent-a.akamaihd.net/ugc/1186083602260480721/31445F0BA3C03342A8BEFF2F14EBE1534FF61421/",
        "Convex": false,
        "MaterialIndex": 1,
        "TypeIndex": 0,
        "CastShadows": true
      }
    }
  ]
}]]
end


--return objects--------------------------------------------------------

function returnAllWallButtons()
    local wallButtons = {}
    
    for i, object in ipairs(buttonIndexMap)do
        if(object.type == "wallButton") then
            table.insert(wallButtons,object)
        end
    end

    return wallButtons
end
