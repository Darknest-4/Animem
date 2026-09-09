
    <div class="container-fluid">
      <div class="row">
        <h3>
          Edit: <?= $title;?>
        </h3>
      </div>
      <form action="<?= BASEURL; ?>/datasheet.php" method="get" id="form2">
        <input type="text" style="display:none;" type="text" name="reform" value="<?= $id;?>" readonly="readonly" form="form2">
      </form>
      <form action="<?= BASEURL; ?>/datasheet.php" method="get" id="form1">
        <input type="text" style="display:none;" type="text" name="save" value="<?= $id;?>" readonly="readonly" form="form1">
          <div class="row">
            <div class="col-12">
              <button type="submit" class="btn btn-success" form="form1">Save</button>
            
              <button type="submit" class="btn btn-info" form="form2">Elöző Visszaállítása</button>
              <a class="btn btn-danger"  onclick="return confirm('Are you sure you want to delete this?')" href="<?= BASEURL;?>datasheet.php?delete=<?= $id; ?>">Delete</a>
              <a class="btn btn-info" target="_blank" href="https://jsoneditoronline.org">JSON Editor</a>
              <a class="btn btn-info" href="<?= BASEURL; ?>datasheet.php?reimage=<?= $myanimelist;?>&id=<?= $id;?>">Re-Image</a>
            </div>
          </div>
          <div class="row">
            <div class="col">
              <div class="table-responsive">
                <table class="table table-striped">
                  <tbody>
                    <tr>
                      <td>ID:</td>
                      <td><span><?= $id;?></span></td>
                    </tr>
                    <tr>
                      <td>Cím:</td>
                      <td><input type="text" class="form-control" type="text" name="title" value="<?= htmlspecialchars($title); ?>" form="form1"></td>
                    </tr>
                    <tr>
                      <td>Link:</td>
                      <td>
                        <div class="input-group">
                          <span class="input-group-text"><?= str_replace('admin/', '', BASEURL); ?></span>
                          <input type="text" class="form-control" name="link" value="<?= $link;?>" form="form1">
                          <span class="input-group-text"><a href="<?= str_replace('admin/', '', BASEURL); ?>DataSheet/<?= $id;?>/<?= $link;?>" target="_blank">Open</a></span>
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td>MyAnimeList:</td>
                      <td>
                        <div class="input-group">
                          <span class="input-group-text">https://myanimelist.net/anime/</span>
                          <input type="text" class="form-control" name="myanimelist" value="<?= $myanimelist;?>" form="form1">
                          <span class="input-group-text"><a href="https://myanimelist.net/anime/<?= $myanimelist;?>" target="_blank">Open</a></span>
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td>Leírás:</td>
                      <td>
                        <div class="input-group">
                          <textarea type="textarea" class="form-control"  name="description" form="form1"><?= $description;?></textarea>
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td>Fansub JSON:</td>
                      <td>
                        <div class="input-group">
                          <textarea type="textarea" class="form-control"  name="fansub" form="form1"><?= json_encode(json_decode($fansub, true)["fansub"]);?></textarea>
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td>SeriesSite</td>
                      <td>
                        <div class="input-group">
                          <select class="form-select" name="datasheet" form="form1">
                            <option value="1" <?php if($datasheet==1) echo "selected";?>>Nem</option>
                            <option value="0" <?php if($datasheet==0) echo "selected";?>>Igen</option>
                          </select>
                          
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td>Series (A-Z listában megjelenik)</td>
                      <td>
                        <div class="input-group">
                          <select class="form-select" name="series" form="form1">
                            <option value="1" <?php if($series==1) echo "selected";?>>Igen</option>
                            <option value="0" <?php if($series==0) echo "selected";?>>Nem</option>
                          </select>
                          
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td>EpisodeList JSON:</td>
                      <td>
                        <div class="input-group">
                          <textarea type="textarea" class="form-control" name="episodelist" form="form1"><?= json_encode(json_decode($links, true)["links"]);?></textarea>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
                
            </div>
            </div>
          </div>
      </form>
    </div>
