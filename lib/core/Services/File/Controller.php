<?php
// (c) Copyright 2002-2013 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id: Controller.php 45185 2013-03-18 15:46:39Z jonnybradley $

class Services_File_Controller
{
	public $defaultGalleryId = 1;

	function setUp()
	{
		global $prefs;

		if ($prefs['feature_file_galleries'] != 'y') {
			throw new Services_Exception_Disabled('feature_file_galleries');
		}
		$this->defaultGalleryId = $prefs['fgal_root_id'];
	}

	function action_upload($input)
	{
		$gal_info = $this->checkTargetGallery($input);

		$size = $input->size->int();
		$name = $input->name->text();
		$type = $input->type->text();
		$data = $input->data->none();
		$fileId = $input->fileId->int();
		$asuser = $input->user->text();

		$data = base64_decode($data);

		$mimelib = TikiLib::lib('mime');
		$type = $mimelib->from_content($name, $data);

		if ($fileId) {
			$this->updateFile($gal_info, $name, $size, $type, $data, $fileId, $asuser);
		} else {
			$fileId = $this->uploadFile($gal_info, $name, $size, $type, $data, $asuser);
		}

		if ($fileId === false) {
			throw new Services_Exception(tr('File could not be uploaded. Restrictions apply.'), 406);
		}

		return array(
			'size' => $size,
			'name' => $name,
			'type' => $type,
			'fileId' => $fileId,
			'galleryId' => $gal_info['galleryId'],
			'md5sum' => md5($data),
		);
	}

	function action_remote($input)
	{
		global $prefs;
		if ($prefs['fgal_upload_from_source'] != 'y') {
			throw new Services_Exception(tr('Upload from source disabled.'), 403);
		}

		$gal_info = $this->checkTargetGallery($input);
		$url = $input->url->url();

		if (! $url) {
			return array(
				'galleryId' => $gal_info['galleryId'],
			);
		}

		$filegallib = TikiLib::lib('filegal');

		if ($file = $filegallib->lookup_source($url)) {
			return $file;
		}

		$info = $filegallib->get_info_from_url($url);

		if (! $info) {
			throw new Services_Exception(tr('Data could not be obtained.'), 412);
		}

		if ($input->reference->int()) {
			$info['data'] = 'REFERENCE';
		}

		$fileId = $this->uploadFile($gal_info, $info['name'], $info['size'], $info['type'], $info['data']);

		if ($fileId === false) {
			throw new Services_Exception(tr('File could not be uploaded. Restrictions apply.'), 406);
		}

		$filegallib->attach_file_source($fileId, $url, $info, $input->reference->int());

		return array(
			'size' => $info['size'],
			'name' => $info['name'],
			'type' => $info['type'],
			'fileId' => $fileId,
			'galleryId' => $gal_info['galleryId'],
			'md5sum' => md5($info['data']),
		);
	}

	function action_refresh($input)
	{
		global $prefs;
		if ($prefs['fgal_upload_from_source'] != 'y') {
			throw new Services_Exception(tr('Upload from source disabled.'), 403);
		}

		if ($prefs['fgal_source_show_refresh'] != 'y') {
			throw new Services_Exception(tr('Manual refresh disabled.'), 403);
		}

		$filegallib = TikiLib::lib('filegal');
		$ret = $filegallib->refresh_file($input->fileId->int());

		return array(
			'success' => $ret,
		);
	}

	/**
	 * @param $input	string "name" for the filename to find
	 * @return array	file info for most recent file by that name
	 */
	function action_find($input)
	{

		$filegallib = TikiLib::lib('filegal');
		$gal_info = $this->checkTargetGallery($input);

		$name = $input->name->text();

		$pos = strpos($name, '?');		// strip off get params
		if ($pos !== false) {
			$name = substr($name, 0, $pos);
		}

		$info = $filegallib->get_file_by_name($gal_info['galleryId'], $name);

		if (empty($info)) {
			$info = $filegallib->get_file_by_name($gal_info['galleryId'], $name, 'filename');
		}

		return $info;
	}

	private function checkTargetGallery($input)
	{
		global $prefs;

		$galleryId = $input->galleryId->int();

		if (empty($galleryId)) $galleryId = $this->defaultGalleryId;

		if (! $gal_info = $this->getGallery($galleryId)) {
			throw new Services_Exception(tr('Requested gallery does not exist.'), 404);
		}

		if ($prefs['feature_use_fgal_for_user_files'] !== 'y' || $gal_info['type'] !== 'user') {
			$perms = Perms::get('file gallery', $galleryId);
			$canUpload = $perms->upload_files;
		} else {
			global $user;
			$perms = TikiLib::lib('tiki')->get_local_perms($user, $galleryId, 'file gallery', $gal_info, false);		//get_perm_object($galleryId, 'file gallery', $galinfo);
			$canUpload = $perms['tiki_p_upload_files'] === 'y';
		}
		if (!$canUpload) {
			throw new Services_Exception(tr('Permission denied.'), 403);
		}

		return $gal_info;
	}

	private function getGallery($galleryId)
	{
		$filegallib = TikiLib::lib('filegal');
		return $filegallib->get_file_gallery_info($galleryId);
	}

	private function uploadFile($gal_info, $name, $size, $type, $data, $asuser = null)
	{
		$filegallib = TikiLib::lib('filegal');
		return $filegallib->upload_single_file($gal_info, $name, $size, $type, $data, $asuser);
	}

	private function updateFile($gal_info, $name, $size, $type, $data, $fileId, $asuser = null)
	{
		$filegallib = TikiLib::lib('filegal');
		return $filegallib->update_single_file($gal_info, $name, $size, $type, $data, $fileId, $asuser);
	}
}
