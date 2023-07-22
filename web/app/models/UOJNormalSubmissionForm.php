<?php

class UOJNormalSubmissionForm extends UOJForm {

    public UOJSubmissionArchive $submission_archive;

    public function __construct($form_name, $requirement, $zip_file_name_gen, $handle) {
        parent::__construct($form_name);

        foreach ($requirement as $req) {
            if ($req['type'] == "source code") {
                $languages = UOJLang::getAvailableLanguages(isset($req['languages']) ? $req['languages'] : null);
                $this->addSourceCodeInput(
                    "{$form_name}_{$req['name']}",
                    UOJLocale::get('problems::source code').':'.$req['name'],
                    $languages
                );
            } elseif ($req['type'] == "text") {
                $this->addTextFileInput(
                    "{$form_name}_{$req['name']}",
                    UOJLocale::get('problems::text file').':'.$req['file_name']
                );
            }
        }

        $this->handle = function(&$vdata) use($requirement, $zip_file_name_gen, $handle) {
            Auth::check() || redirectToLogin();

            try {
                $this->submission_archive = UOJSubmissionArchive::create($zip_file_name_gen);
            } catch (UOJException $e) {
                UOJResponse::message($e->getMessage());
            }
            
            try {
                foreach ($requirement as $req) {
                    $post_name = "{$this->form_name}_{$req['name']}";
                    if ($req['type'] == "source code") {
                        $this->submission_archive->content['config'][] = ["{$req['name']}_language", $_POST["{$post_name}_language"]];
                    }

                    if ($_POST["{$post_name}_upload_type"] == 'editor') {
                        $this->submission_archive->addFromString($req, $_POST["{$post_name}_editor"]);
                    } else {
                        $tmp_name = UOJForm::uploadedFileTmpName("{$post_name}_file");
                        if ($tmp_name == null) {
                            $this->submission_archive->addFromString($req, '');
                        } else {
                            $this->submission_archive->addFromFile($req, $tmp_name);
                        }
                    }
                }
            } catch (UOJException $e) {
                $this->submission_archive->close();
                $this->submission_archive->unlink();
                UOJResponse::message($e->getMessage());
            }

            $this->submission_archive->close();

            $handle($this->submission_archive);
        };
    }
}
