<?php

namespace App\Service;

use ControleOnline\Entity\File;
use ControleOnline\Entity\Import;
use Symfony\Component\Console\Output\OutputInterface;

class EmailService
{
	/* get information specific to this email */
	/*
		$overview = \imap_fetch_overview(self::getConnection(),$email_number,0);
		$message = \imap_fetchbody(self::getConnection(),$email_number,2);    
	*/

	private static $__inbox;
	private static $__config = [];
	private static $__attachments;
	private static $__key = 0;
	private static $__basedir;
	private static $__em;
	private static $__output;
	private static $__remove;
	private static $__error = [];



	public static function getBasedir($folder = 'IMPORT', $relative = false)
	{
		self::$__basedir =
			(!$relative ? dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR : '') .
			'data' . DIRECTORY_SEPARATOR . 'DACTES' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR;

		if (!is_dir(self::$__basedir)) {
			mkdir(self::$__basedir, 0777, true);
		}
		return self::$__basedir;
	}

	public static function makeProcessed($filename, $processed_folder = 'PROCESSED')
	{
		rename(self::getBasedir() . $filename, self::getBasedir($processed_folder) . $filename);
	}

	public static function removeProcessed($filename, $processed_folder = 'PROCESSED')
	{
		unlink(self::getBasedir($processed_folder) . $filename);
	}

	protected static function getConnection()
	{
		if (!self::$__inbox) {
			try {
				$config = self::getConfig();
				self::$__inbox = \imap_open($config['hostname'], $config['username'], $config['password']);
				echo \imap_last_error();
			} catch (\Exception $e) {
				echo $e->getMessage();
				echo \imap_last_error();
			}
		}

		return self::$__inbox;
	}

	protected static function search($search = 'ALL')
	{
		return \imap_search(self::getConnection(), $search);
	}

	protected static function getConfig()
	{
		if (!self::$__config) {
			//self::$__config['hostname'] = Config::getConfig('imap-hostname');//'{imap.gmail.com:993/imap/ssl/novalidate-cert}INBOX';
			//self::$__config['username'] = Config::getConfig('imap-username');//coleta.cte@gmail.com
			//self::$__config['password'] = Config::getConfig('imap-password');//chvorzmquzrzrywr
			self::$__config['hostname'] =  '{imap.gmail.com:993/imap/ssl/novalidate-cert}INBOX';
			self::$__config['username'] = 'coleta.cte@gmail.com';
			self::$__config['password'] = 'chvorzmquzrzrywr';
		}

		return self::$__config;
	}

	protected static function getAtachments($parts, $email_number, &$i)
	{
		foreach ($parts as $part) {
			if ($part->ifdparameters) {
				foreach ($part->dparameters as $object) {
					self::$__output->writeln([
						'Attribute: ' . $object->attribute
					]);
					if (strtolower($object->attribute) == 'filename') {
						$attachment['is_attachment'] = true;
						$attachment['filename'] = $object->value;
					}
					if (strtolower($object->attribute) == 'name') {
						$attachment['is_attachment'] = true;
						$attachment['name'] = $object->value;
					}
				}
			}

			if ($attachment['is_attachment']) {
				$attachment['attachment'] = \imap_fetchbody(self::getConnection(), $email_number, $i + 1);

				self::$__output->writeln([
					'Atachment found',
				]);

				/* 3 = BASE64 encoding */
				if ($part->encoding == 3) {
					$attachment['attachment'] = base64_decode($attachment['attachment']);
				}
				/* 4 = QUOTED-PRINTABLE encoding */ elseif ($part->encoding == 4) {
					$attachment['attachment'] = quoted_printable_decode($attachment['attachment']);
				}
				self::$__attachments[] = $attachment;
			}

			if ($part->parts) {
				self::getAtachments($part->parts, $email_number, $i);
			}
		}
	}


	public static function processAttachments($email_number)
	{
		/* get mail structure */
		$structure = \imap_fetchstructure(self::getConnection(), $email_number);

		$attachments = array();

		/* if any attachments found... */
		if (isset($structure->parts) && count($structure->parts)) {
			self::$__output->writeln([
				'Structure parts:' . count($structure->parts),
			]);
			for ($i = 0; $i < count($structure->parts); $i++) {
				self::$__output->writeln([
					'Part ' . $i,
				]);
				self::getAtachments($structure->parts, $email_number, $i);
			}

			foreach (self::$__attachments as $attachment) {
				if ($attachment['filename']) {
					$extension = pathinfo(self::getBasedir() . $attachment['filename'], PATHINFO_EXTENSION);
					$extension = strtolower($extension);
					self::$__output->writeln([
						'File extension: ' . $extension,
					]);
					file_put_contents(self::getBasedir() . $attachment['filename'], $attachment['attachment']);
					switch ($extension) {
						case 'zip':
							self::getDacteFromZip($email_number, $attachment['filename'], $attachment['attachment']);
							break;
						case 'xml':
							self::importXML($email_number, $attachment['filename']);
							break;
						default:
							unlink(self::getBasedir() . $attachment['filename']);
							break;
					}
				}
			}
		} else {
			self::$__remove[$email_number] = $email_number;
			self::$__output->writeln([
				'',
				'No atachments',
				'',
			]);
		}

		return $attachments;
	}

	protected static function getDacteFromZip($emailNumber, $attachment, $content)
	{

		$zip = new \ZipArchive;
		$res = $zip->open(self::getBasedir() . $attachment);

		self::$__output->writeln([
			'Extract zip file: ' . self::getBasedir() . $attachment,
		]);

		if ($res === TRUE) {
			for ($i = 0; $i < $zip->numFiles; $i++) {

				$stat = $zip->statIndex($i);
				self::$__output->writeln([
					'File extracted: ' . $stat['name'],
				]);
				if (pathinfo($stat['name'], PATHINFO_EXTENSION) === 'xml') {
					self::$__output->writeln([
						'Import extraced zip file: ' . $stat['name'],
					]);
					$content = $zip->getFromIndex($i);
					file_put_contents(self::getBasedir() . $i . '-' . $stat['name'], $content);
					self::importXML($emailNumber, $i . '-' . $stat['name']);
				}
			}
			//self::makeProcessed($attachment);
			//self::removeProcessed($attachment);
			$zip->close();
		} else {
			self::$__error[] = 'Cannot open zip file: ' . self::getBasedir() . $attachment;
			self::$__output->writeln([
				'Cannot open zip file: ' . self::getBasedir() . $attachment,
			]);
		}
	}
	public static function setOutput(OutputInterface $output)
	{
		self::$__output = $output;
	}

	public static function setEm($em)
	{
		self::$__em = $em;
	}

	protected static function importXML($emailNumber, $filename)
	{
		try {

			self::$__output->writeln([
				'',
				'File ' . $filename . ' on queue',
				self::getBasedir('IMPORT', true) . $filename,
				'',
			]);

			$file = self::$__em->getRepository(File::class)->findOneBy([
				'path' => self::getBasedir('IMPORT', true) . $filename
			]);

			if (!$file) {
				$file = new File();
				$file->setUrl($filename);
				$file->setPath(self::getBasedir('IMPORT', true) . $filename);
				self::$__em->persist($file);
				self::$__em->flush();
			} else {
				self::$__output->writeln([
					'',
					'File ' . $filename . ' exists, update.',
					'',
				]);
			}

			$import = self::$__em->getRepository(Import::class)->findOneBy([
				'fileFormat' => 'xml',
				'importType' => 'DACTE',
				'fileId'     => $file->getId()
			]);

			if (!$import) {
				$import = new Import();
				$import->setImportType('DACTE');
				$import->setFileFormat('xml');
				$import->setName($filename);
			}

			$import->setStatus('waiting');
			$import->setFileId($file->getId());

			self::$__em->persist($import);
			self::$__em->flush();
			self::$__remove[$emailNumber] = $emailNumber;
		} catch (\Exception $e) {
			self::$__error[] = $e->getMessage();
			self::$__output->writeln([
				'Error: ' . $e->getMessage()
			]);
		}
	}

	public static function clear()
	{
		if (empty(self::$__error)) {
			foreach (self::$__remove as $email_number) {
				self::remove($email_number);
			}
		}
		self::close();
	}



	public static function getAttachments(int $limit = 1, $search = 'ALL') //$search = 'UNSEEN'
	{
		$i = 0;
		//$folders = \imap_list(self::getConnection(), self::$__config['hostname'], "*");
		$messages    = self::search($search);
		$attachments = [];

		if ($messages) {
			self::$__output->writeln([
				count($messages) . ' messages found',
			]);
			foreach ($messages as $email) {

				self::$__output->writeln([
					'Message:' . $email,
				]);

				$attachments[] = self::processAttachments($email);
				$i++;
				if ($i >= $limit) {
					return $attachments;
				}
			}
		}
		return $attachments;
	}

	public static function remove($email)
	{
		//return \imap_delete(self::getConnection(), $email);
		return \imap_mail_move(self::getConnection(),  4, '[Gmail]/Lixeira');
	}

	public static function close()
	{
		self::clear();
		\imap_expunge(self::getConnection());
		\imap_close(self::getConnection());
	}

	public function __destruct()
	{
		self::close();
	}
}
