<?php 
namespace Core\Util;
use Core\Util\ClassWriter\Uses;
use Core\Util\ClassWriter\Property;
use Core\Util\ClassWriter\Constant;
use Core\Util\ClassWriter\Method;
use ReflectionMethod;
use stdClass;
use Logger;
class MarkdownWriter
{
	const TYPE_TITLE = "title";
	const TYPE_TEXT = "text";
	const TYPE_ANNOTATION = "annotation";
	const TYPE_CODE = "code";
	const TYPE_LIGHT_CODE = "light_code";
	const TYPE_TABLE = "table";
	const TYPE_DATA = "data";

	protected $tokens;
	protected $data;
	public function __construct()
	{
		$this->tokens = [];
		$this->data = [];
	}
	public function data($name, $data)
	{
		$token = new stdClass();
		$token->type = MarkdownWriter::TYPE_DATA;
		$token->title = $name;
		$token->value = $data;

		$this->data[] = $token;
	}
	public function title($content, $level = 1)
	{
		$token = new stdClass();
		$token->type = MarkdownWriter::TYPE_TITLE;
		$token->value = $content;
		$token->level = $level;

		$this->tokens[] = $token;
	}
	public function text($content)
	{
		$token = new stdClass();
		$token->type = MarkdownWriter::TYPE_TEXT;
		$token->value = $content;
		
		$this->tokens[] = $token;
	}
	public function annotation($content)
	{
		$token = new stdClass();
		$token->type = MarkdownWriter::TYPE_ANNOTATION;
		$token->value = $content;
		
		$this->tokens[] = $token;
	}
	public function code($title, $content)
	{
		if(!is_string($content))
		{
			$content = json_encode($content, \JSON_PRETTY_PRINT);
		}
		$token = new stdClass();
		$token->type = MarkdownWriter::TYPE_CODE;
		$token->value = $content;
		$token->title = $title;
		
		$this->tokens[] = $token;
	}
	public function lightcode($content)
	{
		if(!is_string($content))
		{
			$content = json_encode($content, \JSON_PRETTY_PRINT);
		}
		$token = new stdClass();
		$token->type = MarkdownWriter::TYPE_LIGHT_CODE;
		$token->value = $content;
		
		$this->tokens[] = $token;
	}
	public function table($array)
	{
		$token = new stdClass();
		$token->type = MarkdownWriter::TYPE_TABLE;
		$token->value = $array;
		
		$this->tokens[] = $token;
	}
	public function write($path)
	{
		$text = "";
		if(!empty($this->data))
		{
			$text = "---\n";
			foreach($this->data as $data)
			{
				$text.= $data->title.":";
				if(is_string($data->value))
				{
					$text.= " ".$data->value."\n";
				}else
				{
					$text.="\n";
					foreach($data->value as $value)
					{
						$text.="  - ".$value."\n";
					}
				}
				$text.="\n";
			}
			$text.="---\n";
		}
		if(!empty($this->tokens))
		{
			foreach($this->tokens as $token)
			{
				if($token->type === MarkdownWriter::TYPE_TITLE)
				{
					$text.=str_repeat("#", $token->level)." ".$token->value."\n\n";
				}else
				if($token->type == MarkdownWriter::TYPE_CODE)
				{
					$text.="```".$token->title."\n";
					$text.=$token->value."\n";
					$text.="```\n\n";
				}else
				if($token->type == MarkdownWriter::TYPE_LIGHT_CODE)
				{
					$text.="`".$token->value."`\n\n";
				}else
				if($token->type == MarkdownWriter::TYPE_TABLE)
				{
					$first_row = True;
					Logger::info($token->value);
					$data = [];
					$max = 0;
					foreach($token->value as $key=>&$row)
					{
						if(!$first_row)
						{
							$text.=" | ";
						}
						$text.= $key;
						$first_row = False;
						if(is_string($row))
						{
							$row = [$row];
						}
						if(count($row)>$max)
						{
							$max = count($row);
						}
					}
					$text.="\n";
					$first_row = True;
					foreach($token->value as $key=>&$row)
					{
						if(!$first_row)
						{
							$text.=" | ";
						}
						$text.=str_repeat("-", strlen($key));
						$first_row = False;
					}
					$text.="\n";
					$i = 0;
					for($i=0; $i<$max; $i++)
					{
						$data[] = [];
						foreach($token->value as $key=>$row)
						{
							if(is_string($row))
							{
								$row = [$row];
							}
							if(count($row)>$i)
							{
								$data[$i][] = $row[$i];
							}else
							{
								$data[$i][] = "";
							}
						}
					}
					$first_row = True;
					foreach($data as $key=>$row)
					{
						if(is_string($row))
						{
							$row = [$row];
						}
						$first_column = True;
						foreach($row as $column)
						{
							if(!$first_column)
							{
								$text.=" | ";
							}
							if(is_string($column))
							{
								$text.= $column;
							}else
							{
								$text.= json_encode($content, \JSON_PRETTY_PRINT);
							}
							$first_column = False;
						}
						$text.="\n";
						$first_row = False;
					}
					$text.="\n";
				}
			}
		}

		echo $text;
		file_put_contents($path, $text);
	}
}
